<?php

namespace eLife\Search;

use Doctrine\Common\Annotations\AnnotationRegistry;
use eLife\ApiValidator\MessageValidator\JsonMessageValidator;
use eLife\ApiValidator\SchemaFinder\PuliSchemaFinder;
use JMS\Serializer\SerializerBuilder;
use Silex\Application;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use Webmozart\Json\JsonDecoder;

class Kernel
{
    const ROOT = __DIR__.'/../..';

    public static function create(array $config = []) : Application {
        // Create application.
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

        $app->get('/search', function() {
            return "> Search API";
        });

        // DI.
        self::dependencies($app);
        // Routes
        self::routes($app);
        // Validate.
        $app->after(function (Request $request, Response $response) use ($app) {
            // Validation.
            if ($app['config']['validate']) {
                self::validate($app, $request, $response);
            }
        }, 2);

        // Cache.
        $app->after(function (Request $request, Response $response) use ($app) {
            // cache.
            if ($app['config']['ttl'] > 0) {
                self::cache($app, $request, $response);
            }
        }, 3);

        // Error handling.
        $app->error(function (Throwable $e) use ($app) {
            if ($app['debug']) {
                return null;
            }

            return self::handleException($e, $app);
        });


        return $app;
    }

    private static function dependencies($app)
    {
        // Serializer.
        $app['serializer'] = function () {
            return SerializerBuilder::create()->setCacheDir(self::ROOT.'/cache')->build();
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
    }

    private static function routes($app)
    {
    }

    private static function handleException($e, $app)
    {
    }

    private static function cache($app, $request, $response)
    {
    }

    private static function validate($app, $request, $response)
    {
    }

}
