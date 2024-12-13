<?php

namespace tests\eLife\Search\Api\Elasticsearch;

use tests\eLife\Search\RamlRequirement;

final class InterviewElasticsearchTest extends ElasticsearchTestCase
{
    use RamlRequirement;

    public static function jsonProvider() : array
    {
        return [
            [static::getFixtureWithType('interview/v1/minimum.json', 'interview')],
            [static::getFixtureWithType('interview/v1/complete.json', 'interview')],
        ];
    }
}
