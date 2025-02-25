<?php

namespace eLife\Search\Indexer\ModelIndexer;

use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;

class ElasticsearchBackedReviewedPreprintLifecycle implements ReviewedPreprintLifecycle
{
    private MappedElasticsearchClient $client;

    public function __construct(MappedElasticsearchClient $client)
    {
        $this->client = $client;
    }

    public function isSuperseded(string $reviewedPreprintId): bool
    {
        foreach ([
            'research-article',
            'tools-resources',
            'short-report',
            'research-advance',
        ] as $type) {
            if ($this->client->getDocumentById($type.'-'.$reviewedPreprintId, null, true) !== null) {
                return true;
            }
        }

        return false;
    }
}
