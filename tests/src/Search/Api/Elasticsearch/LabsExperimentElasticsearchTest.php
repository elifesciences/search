<?php

namespace tests\eLife\Search\Api\Elasticsearch;

use tests\eLife\Search\RamlRequirement;

final class LabsExperimentElasticsearchTest extends ElasticsearchTestCase
{
    use RamlRequirement;

    public function jsonProvider() : array
    {
        return [
            [$this->getFixtureWithType('labs-experiment/v1/minimum.json', 'labs-experiment')],
            [$this->getFixtureWithType('labs-experiment/v1/complete.json', 'labs-experiment')],
        ];
    }
}
