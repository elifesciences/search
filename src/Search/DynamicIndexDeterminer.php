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
    private Target $target;

    public function __construct(Target $target)
    {
        $this->target = $target;
    }

    public function getCurrentIndexName(): string
    {
        $suffix = match ($this->target) {
            Target::Write => 'write',
            Target::Read => 'read',
        };
        return 'bogus'.$suffix;
    }
}
