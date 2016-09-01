<?php

namespace eLife\Search\Api\Response;

class PodcastEpisodeResponse implements SearchResult
{
    public function getType() : string
    {
        return 'podcast-episode';
    }
}
