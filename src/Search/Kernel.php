<?php

namespace eLife\Search;

use Aws\Sqs\SqsClient;
use Closure;
use ComposerLocator;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\FilesystemCache;
use Elasticsearch\ClientBuilder;
use eLife\ApiClient\HttpClient\BatchingHttpClient;
use eLife\ApiClient\HttpClient\Guzzle6HttpClient;
use eLife\ApiClient\HttpClient\NotifyingHttpClient;
use eLife\ApiProblem\Silex\ApiProblemProvider;
use eLife\ApiSdk\ApiSdk;
use eLife\ApiValidator\MessageValidator\JsonMessageValidator;
use eLife\ApiValidator\SchemaFinder\PathBasedSchemaFinder;
use eLife\Bus\Limit\CompositeLimit;
use eLife\Bus\Limit\LoggingLimit;
use eLife\Bus\Limit\MemoryLimit;
use eLife\Bus\Limit\SignalsLimit;
use eLife\Bus\Queue\Mock\QueueItemTransformerMock;
use eLife\Bus\Queue\Mock\WatchableQueueMock;
use eLife\Bus\Queue\SqsMessageTransformer;
use eLife\Bus\Queue\SqsWatchableQueue;
use eLife\ContentNegotiator\Silex\ContentNegotiationProvider;
use eLife\Logging\Monitoring;
use eLife\Logging\Silex\LoggerProvider;
use eLife\Ping\Silex\PingControllerProvider;
use eLife\Search\Api\ApiValidator;
use eLife\Search\Api\Elasticsearch\Command\BuildIndexCommand;
use eLife\Search\Api\Elasticsearch\ElasticsearchDiscriminator;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use eLife\Search\Api\Elasticsearch\PlainElasticsearchClient;
use eLife\Search\Api\Elasticsearch\SearchResponseSerializer;
use eLife\Search\Api\SearchController;
use eLife\Search\Queue\Command\ImportCommand;
use eLife\Search\Queue\Command\QueueWatchCommand;
use eLife\Search\KeyValueStore\ElasticsearchKeyValueStore;
use eLife\Search\Queue\Workflow;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use JMS\Serializer\EventDispatcher\EventDispatcher;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use JsonSchema\Validator;
use LogicException;
use Psr\Log\LogLevel;
use Silex\Application;
use Silex\Provider;
use Silex\Provider\VarDumperServiceProvider;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class Kernel implements MinimalKernel
{
    const ROOT = __DIR__.'/../..';
    const CACHE_DIR = __DIR__.'/../../var/cache';
    const INDEX_METADATA_KEY = 'index-metadata';

    private $app;

    public function __construct($config = [])
    {
        // Load config
        $config = array_merge([
            'cli' => false,
            'debug' => false,
            'validate' => false,
            'annotation_cache' => true,
            'api_url' => '',
            'api_requests_batch' => 10,
            'ttl' => 300,
            'rate_limit_minimum_page' => 2,
            'elastic_servers' => ['http://localhost:9200'],
            'elastic_logging' => false,
            'elastic_force_sync' => false,
            'elastic_read_client_options' => [
                'timeout' => 3,
                'connect_timeout' => 0.5,
            ],
            'logger.path' => self::ROOT.'/var/logs',
            'logger.level' => LogLevel::INFO,
            'process_memory_limit' => 256,
            'aws' => array_merge([
                'credential_file' => false,
                'mock_queue' => true,
                'queue_name' => 'search--dev',
                'key' => '-----------------------',
                'secret' => '-------------------------------',
                'region' => '---------',
            ], $config['aws'] ?? []),
            'rds_articles' => [],
        ], $config);
        $app = new Application([
            'logger.channel' => 'search',
            'logger.path' => $config['logger.path'],
            'logger.level' => $config['logger.level'],
        ]);
        $app['config'] = $config;
        $app->register(new ApiProblemProvider());
        $app->register(new ContentNegotiationProvider());
        $app->register(new LoggerProvider());
        $app->register(new PingControllerProvider());
        // Annotations.
        AnnotationRegistry::registerAutoloadNamespace(
            'JMS\Serializer\Annotation', ComposerLocator::getPath('jms/serializer').'/src'
        );
        if ($app['config']['debug']) {
            $app->register(new VarDumperServiceProvider());
            $app->register(new Provider\HttpFragmentServiceProvider());
            $app->register(new Provider\ServiceControllerServiceProvider());
            $app->register(new Provider\TwigServiceProvider());
        }
        // DI.
        $this->dependencies($app);
        $this->app = $app;
        $this->applicationFlow($app);
    }

    public function indexMetadata() : IndexMetadata
    {
        return IndexMetadata::fromDocument(
            $this->app['keyvaluestore']->load(
                self::INDEX_METADATA_KEY,
                IndexMetadata::fromContents('elife_search', 'elife_search')->toDocument()
            )
        );
    }

    public function updateIndexMetadata(IndexMetadata $updated)
    {
        $this->app['keyvaluestore']->store(
            self::INDEX_METADATA_KEY,
            $updated->toDocument()
        );
        // deprecated, remove when not read anymore:
        $updated->toFile('index.json');
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
                })
                ->setCacheDir(self::CACHE_DIR)
                ->build();
        };
        $app['serializer.context'] = function () {
            return SerializationContext::create();
        };
        // General cache.
        $app['cache'] = function () {
            return new FilesystemCache(self::CACHE_DIR);
        };

        // PSR-7 Bridge
        $app['psr7.bridge'] = function () {
            return new DiactorosFactory();
        };
        // Validator.
        $app['message-validator'] = function (Application $app) {
            return new JsonMessageValidator(
                new PathBasedSchemaFinder(ComposerLocator::getPath('elife/api').'/dist/model'),
                new Validator()
            );
        };

        $app['validator'] = function (Application $app) {
            return new ApiValidator($app['serializer'], $app['serializer.context'], $app['message-validator'], $app['psr7.bridge']);
        };

        $app['monitoring'] = function (Application $app) {
            return new Monitoring();
        };

        /*
         * @internal
         */
        $app['limit._memory'] = function (Application $app) {
            return MemoryLimit::mb($app['config']['process_memory_limit']);
        };
        /*
         * @internal
         */
        $app['limit._signals'] = function (Application $app) {
            return SignalsLimit::stopOn(['SIGINT', 'SIGTERM', 'SIGHUP']);
        };

        $app['limit.long_running'] = function (Application $app) {
            return new LoggingLimit(
                new CompositeLimit(
                    $app['limit._memory'],
                    $app['limit._signals']
                ),
                $app['logger']
            );
        };

        $app['limit.interactive'] = function (Application $app) {
            return new LoggingLimit(
                $app['limit._signals'],
                $app['logger']
            );
        };

        //#####################################################
        // ------------------ Networking ---------------------
        //#####################################################

        $app['guzzle'] = function (Application $app) {
            // Create default HandlerStack
            $stack = HandlerStack::create();
            $logger = $app['logger'];
            if ($app['config']['debug']) {
                $stack->push(
                    Middleware::mapRequest(function ($request) use ($logger) {
                        $logger->debug("Request performed in Guzzle Middleware: {$request->getUri()}");

                        return $request;
                    })
                );
            }

            return new Client([
                'base_uri' => $app['config']['api_url'],
                'handler' => $stack,
            ]);
        };

        $app['api.sdk'] = function (Application $app) {
            $notifyingHttpClient = new NotifyingHttpClient(
                new BatchingHttpClient(
                    new Guzzle6HttpClient(
                        $app['guzzle']
                    ),
                    $app['config']['api_requests_batch']
                )
            );
            if ($app['config']['debug']) {
                $logger = $app['logger'];
                $notifyingHttpClient->addRequestListener(function ($request) use ($logger) {
                    $logger->debug("Request performed in NotifyingHttpClient: {$request->getUri()}");
                });
            }

            return new ApiSdk($notifyingHttpClient);
        };

        $app['default_controller'] = function (Application $app) {
            return new SearchController(
                $app['serializer'],
                $app['logger'],
                $app['serializer.context'],
                $app['elastic.client.read'],
                $app['config']['api_url'],
                $this->indexMetadata()->read()
            );
        };

        //#####################################################
        // --------------------- Elastic ---------------------
        //#####################################################

        $app['elastic.serializer'] = function (Application $app) {
            return new SearchResponseSerializer($app['serializer']);
        };

        $app['elastic.elasticsearch'] = function (Application $app) {
            $client = ClientBuilder::create();
            // Set hosts.
            $client->setHosts($app['config']['elastic_servers']);
            // Logging for ElasticSearch.
            if ($app['config']['elastic_logging']) {
                $client->setLogger($app['logger']);
            }
            $client->setSerializer($app['elastic.serializer']);

            return $client->build();
        };

        $app['elastic.elasticsearch.plain'] = function (Application $app) {
            $client = ClientBuilder::create();
            // Set hosts.
            $client->setHosts($app['config']['elastic_servers']);
            // Logging for ElasticSearch.
            if ($app['config']['elastic_logging']) {
                $client->setLogger($app['logger']);
            }

            return $client->build();
        };

        $app['keyvaluestore'] = function (Application $app) {
            return new ElasticsearchKeyValueStore(
                new PlainElasticsearchClient(
                    $app['elastic.elasticsearch.plain'],
                    ElasticSearchKeyValueStore::INDEX_NAME
                )
            );
        };

        $app['elastic.client.write'] = function (Application $app) {
            return new MappedElasticsearchClient(
                $app['elastic.elasticsearch'],
                $this->indexMetadata()->operation(IndexMetadata::WRITE),
                $app['config']['elastic_force_sync'],
                $app['config']['elastic_read_client_options']
            );
        };

        $app['elastic.client.read'] = function (Application $app) {
            return new MappedElasticsearchClient(
                $app['elastic.elasticsearch'],
                $this->indexMetadata()->operation(IndexMetadata::READ),
                $app['config']['elastic_force_sync'],
                $app['config']['elastic_read_client_options']
            );
        };

        $app['elastic.client.plain'] = function (Application $app) {
            return new PlainElasticsearchClient(
                $app['elastic.elasticsearch.plain'],
                $this->indexMetadata()->write()
            );
        };

        //#####################################################
        // ------------------ Console DI ----------------------
        //#####################################################

        $app['aws.sqs'] = function (Application $app) {
            $config = [
                'version' => '2012-11-05',
                'region' => $app['config']['aws']['region'],
            ];
            if (isset($app['config']['aws']['endpoint'])) {
                $config['endpoint'] = $app['config']['aws']['endpoint'];
            }
            if (!isset($app['config']['aws']['credential_file']) || false === $app['config']['aws']['credential_file']) {
                $config['credentials'] = [
                    'key' => $app['config']['aws']['key'],
                    'secret' => $app['config']['aws']['secret'],
                ];
            }

            return new SqsClient($config);
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

        // TODO: rename key
        $app['console.queue.import'] = function (Application $app) {
            return new ImportCommand(
                $app['api.sdk'],
                $app['aws.queue'],
                $app['logger'],
                $app['monitoring'],
                $app['limit.interactive']
            );
        };

        $app['workflow'] = function (Application $app) {
            return new Workflow(
                $app['api.sdk']->getSerializer(),
                $app['logger'],
                $app['elastic.client.write'],
                $app['validator'],
                $app['config']['rds_articles']
            );
        };

        $app['console.queue.watch'] = function (Application $app) {
            $mock_queue = $app['config']['aws']['mock_queue'] ?? false;
            if ($mock_queue) {
                return new QueueWatchCommand(
                    $app['mocks.queue'],
                    $app['mocks.queue_transformer'],
                    $app['workflow'],
                    true,
                    $app['logger'],
                    $app['monitoring'],
                    $app['limit.long_running']
                );
            }

            return new QueueWatchCommand(
                $app['aws.queue'],
                $app['aws.queue_transformer'],
                $app['workflow'],
                false,
                $app['logger'],
                $app['monitoring'],
                $app['limit.long_running']
            );
        };

        $app['console.build_index'] = function (Application $app) {
            return new BuildIndexCommand(
                $app['elastic.client.plain'],
                $app['logger']
            );
        };
    }

    public function applicationFlow(Application $app) : Application
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

        if ($app['config']['rate_limit_minimum_page']) {
            $app->after([$this, 'rateLimit'], 4);
        }

        // Return
        return $app;
    }

    public function routes(Application $app)
    {
        $app->get('/search', [$app['default_controller'], 'indexAction'])
            ->before($app['negotiate.accept'](
                'application/vnd.elife.search+json; version=2',
                'application/vnd.elife.search+json; version=1'
            ));

        if ($app['debug']) {
            $app->get('/error', function () use ($app) {
                $app['logger']->debug('Simulating error');
                throw new LogicException('Simulated error');
            });
        }
    }

    public function withApp(callable $fn, $scope = null) : Kernel
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

    public function getApp() : Application
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
                $this->app['message-validator']->validate(
                    $this->app['psr7.bridge']->createResponse($response)
                );
            }
        } catch (Throwable $e) {
            if ($this->app['config']['debug']) {
                throw $e;
            }
        }
    }

    public function cache(Request $request, Response $response) : Response
    {
        $response->setMaxAge($this->app['config']['ttl']);
        $response->headers->addCacheControlDirective('stale-while-revalidate', $this->app['config']['ttl']);
        $response->headers->addCacheControlDirective('stale-if-error', 86400);
        $response->setVary('Accept');
        $response->setEtag(md5($response->getContent()));
        $response->setPublic();
        $response->isNotModified($request);

        return $response;
    }

    public function rateLimit(Request $request, Response $response) : Response
    {
        if ($request->query->get('page', null) >= $this->app['config']['rate_limit_minimum_page']) {
            $response->headers->set('X-Kong-Limit', 'highpages=1');
        }

        return $response;
    }
}
