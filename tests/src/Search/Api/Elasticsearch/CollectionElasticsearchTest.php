<?php

namespace tests\eLife\Search\Api\Elasticsearch;

use eLife\ApiSdk\Client\Collections;
use tests\eLife\Search\RamlRequirement;

final class CollectionElasticsearchTest extends ElasticsearchTestCase
{
    use RamlRequirement;

    public static function jsonProvider() : array
    {
        return [
            [static::getFixtureWithType('collection/v'.Collections::VERSION_COLLECTION.'/minimum.json', 'collection')],
            [static::getFixtureWithType('collection/v'.Collections::VERSION_COLLECTION.'/complete.json', 'collection')],
        ];
    }
}
