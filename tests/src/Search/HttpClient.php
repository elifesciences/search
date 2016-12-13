<?php

namespace tests\eLife\Search;

use Csa\Bundle\GuzzleBundle\GuzzleHttp\Middleware\MockMiddleware;
use Doctrine\Common\Cache\FilesystemCache;
use eLife\ApiClient\HttpClient as SdkClient;
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

    final protected function getHttpClient($record = false) : SdkClient
    {
        if (self::$puli === null) {
            self::setUpPuli();
        }
        if (null === $this->httpClient) {
            $storage = new DoctrineAdapter(new FilesystemCache(__DIR__.'/fixtures'));
            $validator = new JsonMessageValidator(
                new PuliSchemaFinder(self::$puli),
                new JsonDecoder()
            );
            $this->storage = new ValidatingStorageAdapter($storage, $validator);

            $stack = HandlerStack::create();
            $stack->push(new MockMiddleware($this->storage, $record ? 'record' : 'replay'));

            $this->httpClient = new Guzzle6HttpClient(new Client([
                'base_uri' => 'http://localhost:1399/',
                'handler' => $stack,
            ]));
        }

        return $this->httpClient;
    }
}
