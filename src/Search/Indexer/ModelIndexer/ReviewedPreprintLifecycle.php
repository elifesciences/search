<?php

namespace eLife\Search\Indexer\ModelIndexer;

interface ReviewedPreprintLifecycle
{
    public function isSuperseded(string $reviewedPreprintId): bool;
}
