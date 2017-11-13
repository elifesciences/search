<?php

namespace eLife\Search\KeyValueStore;

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
        if ($this->client->indexExists()) {
            // TODO: nothing for now, but we will have to PUT the mapping
        } else {
            // TODO: add enable: false to mapping of its objects
            $this->client->createIndex();
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
            $flush = true
        );
    }

    public function load(string $key) : array
    {
        return $this->client->getPlainDocumentById(
            self::DOCUMENT_TYPE,
            $key
        )['_source'];
    }
}
