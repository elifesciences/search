<?php

namespace tests\eLife\Search\Api\Elasticsearch;

use tests\eLife\Search\RamlRequirement;

final class LabsPostElasticsearchTest extends ElasticsearchTestCase
{
    use RamlRequirement;

    public function jsonProvider() : array
    {
        return [
            [$this->getFixtureWithType('labs-post/v1/minimum.json', 'labs-post')],
            [$this->getFixtureWithType('labs-post/v1/complete.json', 'labs-post')],
        ];
    }
}
