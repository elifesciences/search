<?php

namespace eLife\Search;

use Doctrine\Common\Annotations\AnnotationRegistry;
use eLife\ApiValidator\MessageValidator\JsonMessageValidator;
use eLife\ApiValidator\SchemaFinder\PuliSchemaFinder;
use eLife\Search\Api\SearchController;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use Silex\Application;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Webmozart\Json\JsonDecoder;

class Kernel implements MinimalKernel
{
    const ROOT = __DIR__.'/../..';

    public static $routes = [
        '/search' => 'indexAction',
        '/blog-article' => 'blogArticleAction',
    ];

    private $app;

    public function __construct($config = [])
    {
        $app = new Application();
        // Load config
        $app['config'] = array_merge([
            'debug' => false,
            'validate' => false,
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
        // Serializer.
        $app['serializer'] = function () {
            return SerializerBuilder::create()
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
        $app['default_controller'] = function (Application $app) {
            return new SearchController($app['serializer'], $app['serializer.context']);
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

    public function indexAction()
    {
        return '> Search API';
    }

    public function withApp(callable $fn)
    {
        $fn($this->app);

        return $this;
    }

    public function run()
    {
        return $this->app->run();
    }

    public function validate(Request $request, Response $response)
    {
//        $this->app['puli.validator']->validate(
//            $this->app['psr7.bridge']->createResponse($response)
//        );
    }

    public function cache(Request $request, Response $response)
    {
    }
}
