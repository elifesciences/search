<?php

namespace tests\eLife\Search;

use eLife\ApiClient\HttpClient\Guzzle6HttpClient;
use GuzzleHttp\Client;

trait HttpClient
{
    public static $httpClient;

    final protected static function getHttpClient() : \eLife\ApiClient\HttpClient
    {
        if (null === static::$httpClient) {
            static::$httpClient = new Guzzle6HttpClient(new Client([
                'base_uri' => 'http://api.elifesciences.org',
            ]));
        }

        return static::$httpClient;
    }
}
