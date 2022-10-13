<?php

namespace tests\eLife\Search\Api\Elasticsearch;

use tests\eLife\Search\RamlRequirement;

final class ReviewedPreprintElasticsearchTest extends ElasticsearchTestCase
{
    use RamlRequirement;

    public function jsonProvider() : array
    {
        return [
            [$this->getFixtureWithType('reviewed-preprint/v1/minimum.json', 'reviewed-preprint')],
            [$this->getFixtureWithType('reviewed-preprint/v1/complete.json', 'reviewed-preprint')],
        ];
    }
}
