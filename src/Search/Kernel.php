<?php

namespace eLife\Search;

use Aws\Sqs\SqsClient;
use Closure;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\FilesystemCache;
use Elasticsearch\ClientBuilder;
use eLife\ApiClient\HttpClient\BatchingHttpClient;
use eLife\ApiClient\HttpClient\Guzzle6HttpClient;
use eLife\ApiSdk\ApiSdk;
use eLife\ApiValidator\MessageValidator\JsonMessageValidator;
use eLife\ApiValidator\SchemaFinder\PuliSchemaFinder;
use eLife\Search\Annotation\GearmanTaskDriver;
use eLife\Search\Api\ApiValidator;
use eLife\Search\Api\Elasticsearch\Command\BuildIndexCommand;
use eLife\Search\Api\Elasticsearch\ElasticQueryExecutor;
use eLife\Search\Api\Elasticsearch\ElasticsearchClient;
use eLife\Search\Api\Elasticsearch\ElasticsearchDiscriminator;
use eLife\Search\Api\Elasticsearch\SearchResponseSerializer;
use eLife\Search\Api\SearchController;
use eLife\Search\Api\SearchResultDiscriminator;
use eLife\Search\Api\SubjectStore;
use eLife\Search\Gearman\Command\ApiSdkCommand;
use eLife\Search\Gearman\Command\QueueCommand;
use eLife\Search\Gearman\Command\WorkerCommand;
use eLife\Search\Limit\CompositeLimit;
use eLife\Search\Limit\LoggingMiddleware;
use eLife\Search\Limit\MemoryLimit;
use eLife\Search\Limit\SignalsLimit;
use eLife\Search\Queue\Mock\QueueItemTransformerMock;
use eLife\Search\Queue\Mock\WatchableQueueMock;
use eLife\Search\Queue\SqsMessageTransformer;
use eLife\Search\Queue\SqsWatchableQueue;
use GearmanClient;
use GearmanWorker;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use JMS\Serializer\EventDispatcher\EventDispatcher;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Storage\DoctrineCacheStorage;
use Kevinrob\GuzzleCache\Strategy\PublicCacheStrategy;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\ProcessIdProcessor;
use Psr\Log\NullLogger;
use Silex\Application;
use Silex\Provider;
use Silex\Provider\VarDumperServiceProvider;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use Webmozart\Json\JsonDecoder;

final class Kernel implements MinimalKernel
{
    const ROOT = __DIR__.'/../..';
    const CACHE_DIR = __DIR__.'/../../var/cache';

    public static $routes = [
        '/search' => 'indexAction',
        '/ping' => 'pingAction',
    ];

    private $app;

    public function __construct($config = [])
    {
        $app = new Application();
        // Load config
        $app['config'] = array_merge([
            'cli' => false,
            'debug' => false,
            'validate' => false,
            'annotation_cache' => true,
            'api_url' => '',
            'api_requests_batch' => 10,
            'ttl' => 3600,
            'elastic_servers' => ['http://localhost:9200'],
            'elastic_index' => 'elife_search',
            'file_log_path' => self::ROOT.'/var/logs/all.log',
            'file_error_log_path' => self::ROOT.'/var/logs/error.log',
            'process_memory_limit' => 256,
            'aws' => array_merge([
                'credential_file' => false,
                'mock_queue' => true,
                'queue_name' => 'eLife-search',
                'key' => '-----------------------',
                'secret' => '-------------------------------',
                'region' => '---------',
            ], $config['aws'] ?? []),
        ], $config);
        // Annotations.
        AnnotationRegistry::registerAutoloadNamespace(
            'JMS\Serializer\Annotation', self::ROOT.'/vendor/jms/serializer/src'
        );
        if ($app['config']['debug']) {
            $app->register(new VarDumperServiceProvider());
            $app->register(new Provider\HttpFragmentServiceProvider());
            $app->register(new Provider\ServiceControllerServiceProvider());
            $app->register(new Provider\TwigServiceProvider());
            $app->register(new Provider\WebProfilerServiceProvider(), array(
                'profiler.cache_dir' => self::CACHE_DIR.'/profiler',
                'profiler.mount_prefix' => '/_profiler', // this is the default
            ));
        }
        // DI.
        $this->dependencies($app);
        // Add to class once set up.
        $this->app = $this->applicationFlow($app);
    }

    public function dependencies(Application $app)
    {

        //#####################################################
        // -------------------- Basics -----------------------
        //#####################################################

        // Serializer.
        $app['serializer'] = function () {
            return SerializerBuilder::create()
                ->configureListeners(function (EventDispatcher $dispatcher) {
                    $dispatcher->addSubscriber(new ElasticsearchDiscriminator());
                    $dispatcher->addSubscriber(new SearchResultDiscriminator());
                })
                ->setCacheDir(self::CACHE_DIR)
                ->build();
        };
        $app['serializer.context'] = function () {
            return SerializationContext::create();
        };
        // Puli.
        $app['puli.factory'] = function () {
            $factoryClass = PULI_FACTORY_CLASS;

            return new $factoryClass();
        };
        // Puli repo.
        $app['puli.repository'] = function (Application $app) {
            return $app['puli.factory']->createRepository();
        };
        // General cache.
        $app['cache'] = function () {
            return new FilesystemCache(self::CACHE_DIR);
        };
        // Annotation reader.
        $app['annotations.reader'] = function (Application $app) {
            if ($app['config']['annotation_cache'] === false) {
                return new AnnotationReader();
            }

            return new CachedReader(
                new AnnotationReader(),
                $app['cache'],
                $app['config']['debug']
            );
        };
        // PSR-7 Bridge
        $app['psr7.bridge'] = function () {
            return new DiactorosFactory();
        };
        // Validator.
        $app['puli.validator'] = function (Application $app) {
            return new JsonMessageValidator(
                new PuliSchemaFinder($app['puli.repository']),
                new JsonDecoder()
            );
        };

        $app['validator'] = function (Application $app) {
            return new ApiValidator($app['serializer'], $app['serializer.context'], $app['puli.validator'], $app['psr7.bridge']);
        };

        $app['logger'] = function (Application $app) {
            $logger = new Logger('search-api');
            if ($app['config']['file_log_path']) {
                $stream = new StreamHandler($app['config']['file_log_path'], Logger::DEBUG);
                $stream->pushProcessor(new ProcessIdProcessor());
                $stream->setFormatter(new JsonFormatter());
                $logger->pushHandler($stream);
            }
            if ($app['config']['file_error_log_path']) {
                $stream = new StreamHandler($app['config']['file_error_log_path'], Logger::ERROR);
                $stream->pushProcessor(new ProcessIdProcessor());
                $detailedFormatter = new JsonFormatter();
                $detailedFormatter->includeStacktraces();
                $stream->setFormatter($detailedFormatter);
                $logger->pushHandler($stream);
            }

            return $logger;
        };

        $app['monitoring'] = function (Application $app) {
            return new Monitoring();
        };

        $app['limit'] = function (Application $app) {
            return new LoggingMiddleware(
                new CompositeLimit(
                    MemoryLimit::mb($app['config']['process_memory_limit']),
                    SignalsLimit::stopOn(['SIGINT', 'SIGTERM', 'SIGHUP'])
                ), $app['logger']);
        };

        //#####################################################
        // ------------------ Networking ---------------------
        //#####################################################

        $app['guzzle'] = function (Application $app) {
            // Create default HandlerStack
            $stack = HandlerStack::create();
            $stack->push(
                new CacheMiddleware(
                    new PublicCacheStrategy(
                        new DoctrineCacheStorage(
                            $app['cache']
                        )
                    )
                ),
                'cache'
            );

            return new Client([
                'base_uri' => $app['config']['api_url'],
                'handler' => $stack,
            ]);
        };

        $app['api.sdk'] = function (Application $app) {
            return new ApiSdk(
                new BatchingHttpClient(
                    new Guzzle6HttpClient(
                        $app['guzzle']
                    ),
                    $app['config']['api_requests_batch']
                )
            );
        };

        $app['api.subjects'] = function (Application $app) {
            return new SubjectStore($app['api.sdk']);
        };

        $app['default_controller'] = function (Application $app) {
            return new SearchController($app['serializer'], $app['serializer.context'], $app['elastic.executor'], $app['cache'], $app['config']['api_url'], $app['api.subjects'], $app['config']['elastic_index']);
        };

        //#####################################################
        // --------------------- Elastic ---------------------
        //#####################################################

        // @todo give us some options.
        $app['elastic.logger'] = function () {
            return new NullLogger();
        };

        $app['elastic.serializer'] = function (Application $app) {
            return new SearchResponseSerializer($app['serializer']);
        };

        $app['elastic.elasticsearch'] = function (Application $app) {
            $client = ClientBuilder::create();
            // Set hosts.
            $client->setHosts($app['config']['elastic_servers']);
            // @todo change
            if ($app['config']['debug']) {
                $client->setLogger($app['elastic.logger']);
            }
            $client->setSerializer($app['elastic.serializer']);

            return $client->build();
        };

        $app['elastic.client'] = function (Application $app) {
            return new ElasticsearchClient($app['elastic.elasticsearch'], $app['config']['elastic_index']);
        };

        $app['elastic.executor'] = function (Application $app) {
            return new ElasticQueryExecutor($app['elastic.client']);
        };

        //#####################################################
        // ------------------ Console DI ----------------------
        //#####################################################

        $app['gearman.client'] = function (Application $app) {
            $worker = new GearmanClient();
            foreach ($app['config']['gearman_servers'] as $server) {
                try {
                    $worker->addServer($server);
                } catch (Throwable $e) {
                }
            }

            return $worker;
        };

        $app['gearman.worker'] = function (Application $app) {
            $worker = new GearmanWorker();
            foreach ($app['config']['gearman_servers'] as $server) {
                try {
                    $worker->addServer($server);
                } catch (Throwable $e) {
                }
            }

            return $worker;
        };

        $app['console.gearman.task_driver'] = function (Application $app) {
            return new GearmanTaskDriver(
                $app['annotations.reader'],
                $app['gearman.worker'],
                $app['gearman.client'],
                $app['logger'],
                $app['monitoring'],
                $app['limit']
            );
        };

        $app['aws.sqs'] = function (Application $app) {
            if (isset($app['config']['aws']['credential_file']) && $app['config']['aws']['credential_file'] === true) {
                return new SqsClient([
                    'version' => '2012-11-05',
                    'region' => $app['config']['aws']['region'],
                ]);
            }

            return new SqsClient([
                'credentials' => [
                    'key' => $app['config']['aws']['key'],
                    'secret' => $app['config']['aws']['secret'],
                ],
                'version' => '2012-11-05',
                'region' => $app['config']['aws']['region'],
            ]);
        };

        $app['aws.queue'] = function (Application $app) {
            return new SqsWatchableQueue($app['aws.sqs'], $app['config']['aws']['queue_name']);
        };

        $app['aws.queue_transformer'] = function (Application $app) {
            return new SqsMessageTransformer($app['api.sdk']);
        };

        $app['mocks.queue'] = function () {
            return new WatchableQueueMock();
        };

        $app['mocks.queue_transformer'] = function (Application $app) {
            return new QueueItemTransformerMock($app['api.sdk']);
        };

        $app['console.gearman.worker'] = function (Application $app) {
            return new WorkerCommand($app['api.sdk'], $app['serializer'], $app['console.gearman.task_driver'], $app['elastic.client'], $app['validator'], $app['logger']);
        };

        // TODO: rename key
        $app['console.gearman.client'] = function (Application $app) {
            return new ApiSdkCommand(
                $app['api.sdk'],
                $app['aws.queue'],
                $app['logger'],
                $app['monitoring'],
                $app['limit']
            );
        };

        $app['console.gearman.queue'] = function (Application $app) {
            $mock_queue = $app['config']['aws']['mock_queue'] ?? false;
            if ($mock_queue) {
                return new QueueCommand(
                    $app['mocks.queue'],
                    $app['mocks.queue_transformer'],
                    $app['gearman.client'],
                    true,
                    $app['config']['aws']['queue_name'],
                    $app['logger'],
                    $app['monitoring'],
                    $app['limit']
                );
            }

            return new QueueCommand(
                $app['aws.queue'],
                $app['aws.queue_transformer'],
                $app['gearman.client'],
                false,
                $app['config']['aws']['queue_name'],
                $app['logger'],
                $app['monitoring'],
                $app['limit']
            );
        };

        $app['console.build_index'] = function (Application $app) {
            return new BuildIndexCommand($app['elastic.client'], $app['logger']);
        };
    }

    public function applicationFlow(Application $app): Application
    {
        // Routes
        $this->routes($app);
        // Validate.
        if ($app['config']['validate']) {
            $app->after([$this, 'validate'], 2);
        }
        // Cache.
        if ($app['config']['ttl'] > 0) {
            $app->after([$this, 'cache'], 3);
        }
        // Error handling.
        if (!$app['config']['debug']) {
            $app->error([$this, 'handleException']);
        }
        // Return
        return $app;
    }

    public function routes(Application $app)
    {
        foreach (self::$routes as $route => $action) {
            $app->get($route, [$app['default_controller'], $action]);
        }
    }

    public function handleException(Throwable $e): Response
    {
        return new JsonResponse([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }

    public function withApp(callable $fn, $scope = null)
    {
        $boundFn = Closure::bind($fn, $scope ? $scope : $this);
        $boundFn($this->app);

        return $this;
    }

    public function run()
    {
        return $this->app->run();
    }

    public function get($d)
    {
        return $this->app[$d];
    }

    public function getApp()
    {
        return $this->app;
    }

    public function validate(Request $request, Response $response)
    {
        try {
            if (
                strpos($response->headers->get('Content-Type'), 'json') &&
                !$response instanceof JsonResponse
            ) {
                $this->app['puli.validator']->validate(
                    $this->app['psr7.bridge']->createResponse($response)
                );
            }
        } catch (Throwable $e) {
            if ($this->app['config']['debug']) {
                throw $e;
            }
        }
    }

    public function cache(Request $request, Response $response)
    {
    }
}
