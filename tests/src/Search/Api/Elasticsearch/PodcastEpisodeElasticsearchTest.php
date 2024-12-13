<?php

namespace tests\eLife\Search\Api\Elasticsearch;

use tests\eLife\Search\RamlRequirement;

final class PodcastEpisodeElasticsearchTest extends ElasticsearchTestCase
{
    use RamlRequirement;

    public static function jsonProvider() : array
    {
        return [
            [static::getFixtureWithType('podcast-episode/v1/minimum.json', 'podcast-episode')],
            [static::getFixtureWithType('podcast-episode/v1/complete.json', 'podcast-episode')],
        ];
    }
}
