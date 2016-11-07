<?php

namespace tests\eLife\Search\Api\Elasticsearch;

use tests\eLife\Search\RamlRequirement;

final class InterviewElasticsearchTest extends ElasticsearchTestCase
{
    use RamlRequirement;

    public function jsonProvider() : array
    {
        return [
            [$this->getFixtureWithType('interview/v1/minimum.json', 'interview')],
            [$this->getFixtureWithType('interview/v1/complete.json', 'interview')],
        ];
    }
}
