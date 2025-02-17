<?php

namespace eLife\Search\Api\Elasticsearch;

interface IndexDeterminer
{
    public function getCurrentIndexName(): string;
}
