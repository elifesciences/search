<?php

namespace tests\eLife\Search\Api\Elasticsearch;

use tests\eLife\Search\RamlRequirement;

final class EventElasticsearchTest extends ElasticsearchTestCase
{
    use RamlRequirement;

    public function jsonProvider() : array
    {
        return [
            [$this->getFixtureWithType('event/v1/minimum.json', 'event')],
            [$this->getFixtureWithType('event/v1/complete.json', 'event')],
        ];
    }
}
