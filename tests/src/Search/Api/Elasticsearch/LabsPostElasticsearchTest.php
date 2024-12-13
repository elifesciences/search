<?php

namespace tests\eLife\Search\Api\Elasticsearch;

use tests\eLife\Search\RamlRequirement;

final class LabsPostElasticsearchTest extends ElasticsearchTestCase
{
    use RamlRequirement;

    public static function jsonProvider() : array
    {
        return [
            [static::getFixtureWithType('labs-post/v1/minimum.json', 'labs-post')],
            [static::getFixtureWithType('labs-post/v1/complete.json', 'labs-post')],
        ];
    }
}
