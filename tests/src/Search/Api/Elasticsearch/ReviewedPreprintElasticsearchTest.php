<?php

namespace tests\eLife\Search\Api\Elasticsearch;

use tests\eLife\Search\RamlRequirement;

final class ReviewedPreprintElasticsearchTest extends ElasticsearchTestCase
{
    use RamlRequirement;

    public static function jsonProvider() : array
    {
        return [
            [static::getFixtureWithType('reviewed-preprint/v1/minimum.json', 'reviewed-preprint')],
            [static::getFixtureWithType('reviewed-preprint/v1/complete.json', 'reviewed-preprint')],
        ];
    }
}
