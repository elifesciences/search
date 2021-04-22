<?php

namespace eLife\Search\KeyValueStore;

use Elasticsearch\Common\Exceptions\Missing404Exception;
use eLife\Search\Api\Elasticsearch\PlainElasticsearchClient;

final class ElasticsearchKeyValueStore implements KeyValueStore
{
    private $client;
    const INDEX_NAME = 'key_value_store'; // analogue to elife_search

    public function __construct(PlainElasticsearchClient $client)
    {
        $this->client = $client;
    }

    public function setup()
    {
        if (!$this->client->indexExists()) {
            $this->client->createIndex(
                $indexName = null, // use the default configured in $this->client
                $additionalParams = [
                    'body' => [
                        'settings' => [
                            'number_of_shards' => 1,
                        ],
                    ],
                ]
            );
        }
    }

    /**
     * @param mixed $value
     */
    public function store(string $key, array $value)
    {
        $this->client->indexJsonDocument(
            $key,
            json_encode($value)
        );
    }

    public function load(string $key) : array
    {
        return $this->client->getDocumentById(
            $key
        )['_source'];
    }
}
