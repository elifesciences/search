<?php

namespace eLife\Search;

use eLife\Search\Api\Elasticsearch\IndexDeterminer;
use eLife\Search\KeyValueStore\KeyValueStore;

enum Target
{
    case Write;
    case Read;
}

class DynamicIndexDeterminer implements IndexDeterminer
{
    const INDEX_METADATA_KEY = 'index-metadata';

    private Target $target;
    private KeyValueStore $keyValueStore;

    public function __construct(KeyValueStore $keyValueStore, Target $target)
    {
        $this->target = $target;
        $this->keyValueStore = $keyValueStore;
    }

    public function getCurrentIndexName(): string
    {
        IndexMetadata::fromDocument(
            $this->keyValueStore->load(
                self::INDEX_METADATA_KEY,
                IndexMetadata::fromContents('elife_search', 'elife_search')->toDocument()
            )
        );

        $suffix = match ($this->target) {
            Target::Write => 'write',
            Target::Read => 'read',
        };
        return 'bogus'.$suffix;
    }
}
