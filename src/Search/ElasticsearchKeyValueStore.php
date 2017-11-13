<?php

namespace eLife\Search;

use eLife\Search\Api\Elasticsearch\ElasticsearchClient;

final class ElasticsearchKeyValueStore implements KeyValueStore
{
    private $client;
    const INDEX_NAME = 'key-value-store';
    const DOCUMENT_TYPE = 'json_object';
    
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
     * @param mixed $value
     */
    public function store(string $key, array $value)
    {
        $this->client->indexJsonDocument(
            self::DOCUMENT_TYPE,
            $key,
            json_encode($value),
            $flush = true,
            self::INDEX_NAME
        );
    }

    public function load(string $key) : array
    {
        return $this->client->getPlainDocumentById(
            self::DOCUMENT_TYPE,
            $key,
            self::INDEX_NAME
        );
    }
}
