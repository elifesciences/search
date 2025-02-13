<?php

namespace eLife\Search;

use Aws\Sqs\SqsClient;
use Closure;
use ComposerLocator;
use Doctrine\Common\Annotations\AnnotationRegistry;
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
use eLife\Search\Api\Elasticsearch\Command\SearchSetupCommand;
use eLife\Search\Api\Elasticsearch\ElasticsearchDiscriminator;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use eLife\Search\Api\Elasticsearch\PlainElasticsearchClient;
use eLife\Search\Api\Elasticsearch\SearchResponseSerializer;
use eLife\Search\Api\SearchController;
use eLife\Search\Queue\Command\ImportCommand;
use eLife\Search\Queue\Command\QueueWatchCommand;
use eLife\Search\KeyValueStore\ElasticsearchKeyValueStore;
use eLife\Search\Indexer\Indexer;
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
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use Pimple\Container;
use Pimple\Psr11\Container as PimplePsr11Container;
use Psr\Container\ContainerInterface;
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

    private Container $pimple;

    private ContainerInterface $container;

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
            'elastic_username' => false,
            'elastic_password' => false,
            'elastic_ssl_verification' => true,
            'elastic_read_client_options' => [
                'timeout' => 3,
                'connect_timeout' => 0.5,
            ],
            'logger.path' => self::ROOT.'/var/logs',
            'logger.level' => LogLevel::INFO,
            'process_memory_limit' => 256,
            'aws' => array_merge([
                'credential_file' => false,
                'queue_name' => 'search--dev',
                'key' => '-----------------------',
                'secret' => '-------------------------------',
                'region' => '---------',
            ], $config['aws'] ?? []),
            'rds_articles' => [],
        ], $config);

        // Create a container
        $app = new Application([
            'logger.channel' => 'search',
            'logger.path' => $config['logger.path'],
            'logger.level' => $config['logger.level'],
        ]);

        $this->pimple = $app;
        $this->pimple['config'] = $config;

        // Register dependencies
        $this->pimple->register(new ApiProblemProvider());
        $this->pimple->register(new ContentNegotiationProvider());
        $this->pimple->register(new LoggerProvider());
        $this->pimple->register(new PingControllerProvider());
        // Annotations.
        AnnotationRegistry::registerLoader('class_exists');
        if ($this->pimple['config']['debug']) {
            $this->pimple->register(new VarDumperServiceProvider());
            $this->pimple->register(new Provider\HttpFragmentServiceProvider());
            $this->pimple->register(new Provider\ServiceControllerServiceProvider());
            $this->pimple->register(new Provider\TwigServiceProvider());
        }
        $this->dependencies($this->pimple);
        $this->container = new PimplePsr11Container($app);

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

    public function dependencies(Container $container)
    {
        //#####################################################
        // -------------------- Basics -----------------------
        //#####################################################

        // Serializer.
        $container['serializer'] = function () {
            return SerializerBuilder::create()
                ->configureListeners(function (EventDispatcher $dispatcher) {
                    $dispatcher->addSubscriber(new ElasticsearchDiscriminator());
                })
                ->setCacheDir(self::CACHE_DIR)
                ->build();
        };
        $container['serializer.context'] = function () {
            return SerializationContext::create();
        };

        // PSR-7 Bridge
        $container['psr17factory'] = function () {
            return new Psr17Factory();
        };
        $container['psr7.bridge'] = function (Container $container) {
            $psr17Factory = $container['psr17factory'];
            return new PsrHttpFactory(
                $psr17Factory,
                $psr17Factory,
                $psr17Factory,
                $psr17Factory
            );
        };
        // Validator.
        $container['message-validator'] = function (Container $container) {
            return new JsonMessageValidator(
                new PathBasedSchemaFinder(ComposerLocator::getPath('elife/api').'/dist/model'),
                new Validator()
            );
        };

        $container['validator'] = function (Container $container) {
            return new ApiValidator($container['serializer'], $container['serializer.context'], $container['message-validator'], $container['psr7.bridge']);
        };

        $container['monitoring'] = function (Container $container) {
            return new Monitoring();
        };

        /*
         * @internal
         */
        $container['limit._memory'] = function (Container $container) {
            return MemoryLimit::mb($container['config']['process_memory_limit']);
        };
        /*
         * @internal
         */
        $container['limit._signals'] = function (Container $container) {
            return SignalsLimit::stopOn(['SIGINT', 'SIGTERM', 'SIGHUP']);
        };

        $container['limit.long_running'] = function (Container $container) {
            return new LoggingLimit(
                new CompositeLimit(
                    $container['limit._memory'],
                    $container['limit._signals']
                ),
                $container['logger']
            );
        };

        $container['limit.interactive'] = function (Container $container) {
            return new LoggingLimit(
                $container['limit._signals'],
                $container['logger']
            );
        };

        //#####################################################
        // ------------------ Networking ---------------------
        //#####################################################

        $container['guzzle'] = function (Container $container) {
            // Create default HandlerStack
            $stack = HandlerStack::create();
            $logger = $container['logger'];
            if ($container['config']['debug']) {
                $stack->push(
                    Middleware::mapRequest(function ($request) use ($logger) {
                        $logger->debug("Request performed in Guzzle Middleware: {$request->getUri()}");

                        return $request;
                    })
                );
            }

            return new Client([
                'base_uri' => $container['config']['api_url'],
                'handler' => $stack,
            ]);
        };

        $container['api.sdk'] = function (Container $container) {
            $notifyingHttpClient = new NotifyingHttpClient(
                new BatchingHttpClient(
                    new Guzzle6HttpClient(
                        $container['guzzle']
                    ),
                    $container['config']['api_requests_batch']
                )
            );
            if ($container['config']['debug']) {
                $logger = $container['logger'];
                $notifyingHttpClient->addRequestListener(function ($request) use ($logger) {
                    $logger->debug("Request performed in NotifyingHttpClient: {$request->getUri()}");
                });
            }

            return new ApiSdk($notifyingHttpClient);
        };

        $container['default_controller'] = function (Container $container) {
            return new SearchController(
                $container['serializer'],
                $container['logger'],
                $container['serializer.context'],
                $container['elastic.client.read'],
                $this->indexMetadata()->read()
            );
        };

        //#####################################################
        // --------------------- Elastic ---------------------
        //#####################################################

        $container['elastic.serializer'] = function (Container $container) {
            return new SearchResponseSerializer($container['serializer']);
        };

        $container['elastic.elasticsearch'] = function (Container $container) {
            $client = ClientBuilder::create();
            // Set hosts.
            $client->setHosts($container['config']['elastic_servers']);
            // Logging for ElasticSearch.
            if ($container['config']['elastic_logging']) {
                $client->setLogger($container['logger']);
            }
            if ($container['config']['elastic_username'] && $container['config']['elastic_password']) {
                $client->setBasicAuthentication($container['config']['elastic_username'], $container['config']['elastic_password']);
            }
            $client->setSSLVerification($container['config']['elastic_ssl_verification']);
            $client->setSerializer($container['elastic.serializer']);

            return $client->build();
        };

        $container['elastic.elasticsearch.plain'] = function (Container $container) {
            $client = ClientBuilder::create();
            // Set hosts.
            $client->setHosts($container['config']['elastic_servers']);
            // Logging for ElasticSearch.
            if ($container['config']['elastic_logging']) {
                $client->setLogger($container['logger']);
            }
            if ($container['config']['elastic_username'] && $container['config']['elastic_password']) {
                $client->setBasicAuthentication($container['config']['elastic_username'], $container['config']['elastic_password']);
            }
            $client->setSSLVerification($container['config']['elastic_ssl_verification']);

            return $client->build();
        };

        $container['keyvaluestore'] = function (Container $container) {
            return new ElasticsearchKeyValueStore(
                new PlainElasticsearchClient(
                    $container['elastic.elasticsearch.plain'],
                    ElasticSearchKeyValueStore::INDEX_NAME
                )
            );
        };

        $container['elastic.client.write'] = function (Container $container) {
            return new MappedElasticsearchClient(
                $container['elastic.elasticsearch'],
                $this->indexMetadata()->operation(IndexMetadata::WRITE),
                new DynamicIndexDeterminer(Target::Write),
                $container['config']['elastic_force_sync'],
                $container['config']['elastic_read_client_options']
            );
        };

        $container['elastic.client.read'] = function (Container $container) {
            return new MappedElasticsearchClient(
                $container['elastic.elasticsearch'],
                $this->indexMetadata()->operation(IndexMetadata::READ),
                new DynamicIndexDeterminer(Target::Read),
                $container['config']['elastic_force_sync'],
                $container['config']['elastic_read_client_options']
            );
        };

        $container['elastic.client.plain'] = function (Container $container) {
            return new PlainElasticsearchClient(
                $container['elastic.elasticsearch.plain'],
                $this->indexMetadata()->write()
            );
        };

        //#####################################################
        // ------------------ Console DI ----------------------
        //#####################################################

        $container['aws.sqs'] = function (Container $container) {
            $config = [
                'version' => '2012-11-05',
                'region' => $container['config']['aws']['region'],
            ];
            if (isset($container['config']['aws']['endpoint'])) {
                $config['endpoint'] = $container['config']['aws']['endpoint'];
            }
            if (!isset($container['config']['aws']['credential_file']) || false === $container['config']['aws']['credential_file']) {
                $config['credentials'] = [
                    'key' => $container['config']['aws']['key'],
                    'secret' => $container['config']['aws']['secret'],
                ];
            }

            return new SqsClient($config);
        };

        $container['aws.queue'] = function (Container $container) {
            return new SqsWatchableQueue($container['aws.sqs'], $container['config']['aws']['queue_name']);
        };

        $container['aws.queue_transformer'] = function (Container $container) {
            return new SqsMessageTransformer($container['api.sdk']);
        };

        $container['mocks.queue'] = function () {
            return new WatchableQueueMock();
        };

        $container['mocks.queue_transformer'] = function (Container $container) {
            return new QueueItemTransformerMock($container['api.sdk']);
        };

        // TODO: rename key
        $container['console.queue.import'] = function (Container $container) {
            return new ImportCommand(
                $container['api.sdk'],
                $container['aws.queue'],
                $container['logger'],
                $container['monitoring'],
                $container['limit.interactive']
            );
        };

        $container['indexer'] = function (Container $container) {
            return new Indexer(
                $container['logger'],
                $container['elastic.client.write'],
                $container['validator'],
                Indexer::getDefaultModelIndexers(
                    $container['api.sdk']->getSerializer(),
                    $container['elastic.client.write'],
                    $container['config']['rds_articles']
                )
            );
        };

        $container['console.queue.watch'] = function (Container $container) {
            return new QueueWatchCommand(
                $container['aws.queue'],
                $container['aws.queue_transformer'],
                $container['indexer'],
                $container['logger'],
                $container['monitoring'],
                $container['limit.long_running']
            );
        };

        $container['console.build_index'] = function (Container $container) {
            return new SearchSetupCommand(
                $container['elastic.client.plain'],
                $container['logger']
            );
        };
    }

    public function applicationFlow(Application $app) : Application
    {
        // Routes
        $this->routes($app);
        // Validate.
        if ($this->container->get('config')['validate']) {
            $app->after([$this, 'validate'], 2);
        }
        // Cache.
        if ($this->container->get('config')['ttl'] > 0) {
            $app->after([$this, 'cache'], 3);
        }

        if ($this->container->get('config')['rate_limit_minimum_page']) {
            $app->after([$this, 'rateLimit'], 4);
        }

        // Return
        return $app;
    }

    public function routes(Application $app)
    {
        $app->get('/search', [$this->container->get('default_controller'), 'indexAction'])
            ->before($this->container->get('negotiate.accept')(
                'application/vnd.elife.search+json; version=2',
                'application/vnd.elife.search+json; version=1'
            ));

        if ($app['debug']) {
            $app->get('/error', function () {
                $this->container->get('logger')->debug('Simulating error');
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
                $this->container->get('message-validator')->validate(
                    $this->container->get('psr7.bridge')->createResponse($response)
                );
            }
        } catch (Throwable $e) {
            if ($this->container->get('config')['debug']) {
                throw $e;
            }
        }
    }

    public function cache(Request $request, Response $response) : Response
    {
        $response->setMaxAge($this->container->get('config')['ttl']);
        $response->headers->addCacheControlDirective('stale-while-revalidate', $this->container->get('config')['ttl']);
        $response->headers->addCacheControlDirective('stale-if-error', '86400');
        $response->setVary('Accept');
        $response->setEtag(md5($response->getContent()));
        $response->setPublic();
        $response->isNotModified($request);

        return $response;
    }

    public function rateLimit(Request $request, Response $response) : Response
    {
        if ($request->query->get('page', null) >= $this->container->get('config')['rate_limit_minimum_page']) {
            $response->headers->set('X-Kong-Limit', 'highpages=1');
        }

        return $response;
    }
}
