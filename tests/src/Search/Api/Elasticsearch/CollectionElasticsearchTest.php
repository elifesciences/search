<?php

namespace tests\eLife\Search\Api\Elasticsearch;

use tests\eLife\Search\RamlRequirement;

final class CollectionElasticsearchTest extends ElasticsearchTestCase
{
    use RamlRequirement;

    public function jsonProvider() : array
    {
        return [
            [$this->getFixtureWithType('collection/v1/minimum.json', 'collection')],
            [$this->getFixtureWithType('collection/v1/complete.json', 'collection')],
        ];
    }
}
