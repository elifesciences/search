<?php

namespace tests\eLife\Search\Api\Elasticsearch;

use tests\eLife\Search\RamlRequirement;

final class CollectionElasticsearchTest extends ElasticsearchTestCase
{
    use RamlRequirement;

    public static function jsonProvider() : array
    {
        return [
            [static::getFixtureWithType('collection/v2/minimum.json', 'collection')],
            [static::getFixtureWithType('collection/v2/complete.json', 'collection')],
        ];
    }
}
