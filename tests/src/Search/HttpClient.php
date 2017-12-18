<?php

namespace tests\eLife\Search;

use ComposerLocator;
use Csa\GuzzleHttp\Middleware\Cache\MockMiddleware;
use eLife\ApiClient\HttpClient\Guzzle6HttpClient;
use eLife\ApiValidator\MessageValidator\JsonMessageValidator;
use eLife\ApiValidator\SchemaFinder\PathBasedSchemaFinder;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use JsonSchema\Validator;

trait HttpClient
{
    /** @var ValidatingStorageAdapter */
    public $storage;
    public $httpClient;

    final protected function getHttpClient() : \eLife\ApiClient\HttpClient
    {
        if (null === $this->httpClient) {
            $storage = new InMemoryStorageAdapter();
            $validator = new JsonMessageValidator(
                new PathBasedSchemaFinder(ComposerLocator::getPath('elife/api').'/dist/model'),
                new Validator()
            );

            $this->storage = new ValidatingStorageAdapter($storage, $validator);

            $stack = HandlerStack::create();
            $stack->push(new MockMiddleware($this->storage, 'replay'));

            $this->httpClient = new Guzzle6HttpClient(new Client([
                'base_uri' => 'http://api.elifesciences.org',
                'handler' => $stack,
            ]));
        }

        return $this->httpClient;
    }
}
