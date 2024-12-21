<?php

namespace tests\eLife\Search\Indexer\ModelIndexer;

use eLife\ApiSdk\ApiSdk;
use Symfony\Component\Serializer\Serializer;
use tests\eLife\Search\HttpClient;

trait GetSerializer
{
    use HttpClient;

    private static Serializer|null $serializer = null;

    public static function getSerializer() : Serializer
    {
        if (null === static::$serializer) {
            $sdk = new ApiSdk(static::getHttpClient());
            static::$serializer = $sdk->getSerializer();
        }

        return static::$serializer;
    }
}
