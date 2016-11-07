<?php

namespace tests\eLife\Search\Api\Elasticsearch;

use tests\eLife\Search\RamlRequirement;

final class PodcastEpisodeElasticsearchTest extends ElasticsearchTestCase
{
    use RamlRequirement;

    public function jsonProvider() : array
    {
        return [
            [$this->getFixtureWithType('podcast-episode/v1/minimum.json', 'podcast-episode')],
            [$this->getFixtureWithType('podcast-episode/v1/complete.json', 'podcast-episode')],
        ];
    }
}
