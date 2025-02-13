<?php

namespace eLife\Search;

use eLife\Search\Api\Elasticsearch\IndexDeterminer;

enum Target
{
    case Write;
    case Read;
}

class DynamicIndexDeterminer implements IndexDeterminer
{
    /** @phpstan-ignore property.onlyWritten */
    private $target;

    public function __construct(Target $target)
    {
        $this->target = $target;
    }

    public function getCurrentIndexName(): string
    {
        return 'bogus';
    }
}
