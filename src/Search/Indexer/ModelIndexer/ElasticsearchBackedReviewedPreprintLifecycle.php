<?php

namespace eLife\Search\Indexer\ModelIndexer;

class ElasticsearchBackedReviewedPreprintLifecycle implements ReviewedPreprintLifecycle
{
    public function isSuperseded(string $reviewedPreprintId): bool
    {
        return false;
    }
}
