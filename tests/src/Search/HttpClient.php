<?php

namespace tests\eLife\Search;

use eLife\ApiClient\HttpClient\Guzzle6HttpClient;
use GuzzleHttp\Client;
use eLife\ApiClient\HttpClient as ApiHttpClient;

trait HttpClient
{
    public static ApiHttpClient $httpClient;

    final protected static function getHttpClient() : ApiHttpClient
    {
        if (null === static::$httpClient) {
            static::$httpClient = new Guzzle6HttpClient(new Client([
                'base_uri' => 'http://api.elifesciences.org',
            ]));
        }

        return static::$httpClient;
    }
}
