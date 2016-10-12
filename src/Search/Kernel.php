<?php

namespace eLife\Search;

use Closure;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\FilesystemCache;
use Elasticsearch\ClientBuilder;
use eLife\ApiClient\HttpClient\Guzzle6HttpClient;
use eLife\ApiSdk\ApiSdk;
use eLife\ApiValidator\MessageValidator\JsonMessageValidator;
use eLife\ApiValidator\SchemaFinder\PuliSchemaFinder;
use eLife\Search\Annotation\GearmanTaskDriver;
use eLife\Search\Api\Elasticsearch\ElasticsearchClient;
use eLife\Search\Api\Elasticsearch\ElasticsearchDiscriminator;
use eLife\Search\Api\Elasticsearch\SearchResponseSerializer;
use eLife\Search\Api\SearchController;
use eLife\Search\Api\SearchResultDiscriminator;
use eLife\Search\Api\SubjectStore;
use eLife\Search\Gearman\Command\ApiSdkCommand;
use eLife\Search\Gearman\Command\WorkerCommand;
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
use Monolog\Logger;
use Psr\Log\NullLogger;
use Silex\Application;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use Webmozart\Json\JsonDecoder;

final class Kernel implements MinimalKernel
{
    const ROOT = __DIR__.'/../..';

    public static $routes = [
        '/search' => 'indexAction',
        '/test-search' => 'searchTestAction',
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
            'ttl' => 3600,
            'elastic_servers' => ['http://localhost:9200'],
            'elastic_index' => 'elife_search',
            'gearman_auto_restart' => true,
        ], $config);
        // Annotations.
        AnnotationRegistry::registerAutoloadNamespace(
            'JMS\Serializer\Annotation', self::ROOT.'/vendor/jms/serializer/src'
        );
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
                ->setCacheDir(self::ROOT.'/cache')
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
            return new FilesystemCache(self::ROOT.'/cache');
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
                new Guzzle6HttpClient(
                    $app['guzzle']
                )
            );
        };

        $app['api.subjects'] = function (Application $app) {
            return new SubjectStore($app['api.sdk']);
        };

        $app['default_controller'] = function (Application $app) {
            return new SearchController($app['serializer'], $app['serializer.context'], $app['cache'], $app['config']['api_url'], $app['api.subjects']);
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
            return new GearmanTaskDriver($app['annotations.reader'], $app['gearman.worker'], $app['gearman.client'], $app['config']['gearman_auto_restart']);
        };

        $app['console.gearman.worker'] = function (Application $app) {
            return new WorkerCommand($app['api.sdk'], $app['serializer'], $app['console.gearman.task_driver']);
        };

        $app['console.gearman.client'] = function (Application $app) {
            return new ApiSdkCommand($app['api.sdk'], $app['gearman.client']);
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

    public function handleException($e) : Response
    {
    }

    public function withApp(callable $fn)
    {
        $boundFn = Closure::bind($fn, $this);
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

    public function validate(Request $request, Response $response)
    {
        try {
            $this->app['puli.validator']->validate(
                $this->app['psr7.bridge']->createResponse($response)
            );
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
