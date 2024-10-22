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
    public $httpClient;

    final protected function getHttpClient() : \eLife\ApiClient\HttpClient
    {
        if (null === $this->httpClient) {
            $this->httpClient = new Guzzle6HttpClient(new Client([
                'base_uri' => 'http://api.elifesciences.org',
            ]));
        }

        return $this->httpClient;
    }
}
