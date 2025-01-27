<?php

namespace tests\eLife\Search\Api\Elasticsearch;

use eLife\ApiSdk\Client\PodcastEpisodes;
use tests\eLife\Search\RamlRequirement;

final class PodcastEpisodeElasticsearchTest extends ElasticsearchTestCase
{
    use RamlRequirement;

    public static function jsonProvider() : array
    {
        return [
            [static::getFixtureWithType('podcast-episode/v'.PodcastEpisodes::VERSION_PODCAST_EPISODE.'/minimum.json', 'podcast-episode')],
            [static::getFixtureWithType('podcast-episode/v'.PodcastEpisodes::VERSION_PODCAST_EPISODE.'/complete.json', 'podcast-episode')],
        ];
    }
}
