<?php

namespace tests\eLife\Search;

use Csa\Bundle\GuzzleBundle\GuzzleHttp\Middleware\MockMiddleware;
use eLife\ApiClient\HttpClient\Guzzle6HttpClient;
use eLife\ApiValidator\MessageValidator\JsonMessageValidator;
use eLife\ApiValidator\SchemaFinder\PuliSchemaFinder;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use test\eLife\ApiSdk\ValidatingStorageAdapter;
use Webmozart\Json\JsonDecoder;

trait HttpClient
{
    use PuliAware;

    /** @var ValidatingStorageAdapter */
    public $storage;
    public $httpClient;

    final protected function getHttpClient() : \eLife\ApiClient\HttpClient
    {
        if (self::$puli === null) {
            self::setUpPuli();
        }
        if (null === $this->httpClient) {
            $storage = new InMemoryStorageAdapter();
            $validator = new JsonMessageValidator(
                new PuliSchemaFinder(self::$puli),
                new JsonDecoder()
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
