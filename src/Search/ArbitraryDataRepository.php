<?php

namespace eLife\Search;

use eLife\Search\Api\Elasticsearch\ElasticsearchClient;

// TODO: rename to ElasticsearchArbitraryDataRepository
// TODO: Extract Interface with the current name
final class ArbitraryDataRepository
{
    private $client;
    const INDEX_NAME = 'arbitrary';
    
    public function __construct(ElasticsearchClient $client)
    {
        $this->client = $client;
    }

    public function setup()
    {
        if ($this->client->indexExists(self::INDEX_NAME)) {
            // TODO: nothing for now, but we will have to PUT the mapping
        } else {
            $this->client->createIndex(
                self::INDEX_NAME
                // TODO: add enable: false to mapping of its objects
            );
        }
    }

    /**
     *
     */
    public function load(string $key) : array
    {
    }
}
