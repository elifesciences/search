<?php

namespace eLife\Search;

use eLife\Search\Api\Elasticsearch\IndexDeterminer;

class DynamicIndexDeterminer implements IndexDeterminer
{
    public function getCurrentIndexName(): string
    {
        return 'bogus';
    }
}
