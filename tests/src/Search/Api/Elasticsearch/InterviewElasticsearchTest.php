<?php

namespace tests\eLife\Search\Api\Elasticsearch;

use eLife\ApiSdk\Client\Interviews;
use tests\eLife\Search\RamlRequirement;

final class InterviewElasticsearchTest extends ElasticsearchTestCase
{
    use RamlRequirement;

    public static function jsonProvider() : array
    {
        return [
            [static::getFixtureWithType('interview/v'.Interviews::VERSION_INTERVIEW.'/minimum.json', 'interview')],
            [static::getFixtureWithType('interview/v'.Interviews::VERSION_INTERVIEW.'/complete.json', 'interview')],
        ];
    }
}
