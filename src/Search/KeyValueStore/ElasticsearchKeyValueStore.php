<?php

namespace eLife\Search\KeyValueStore;

use Elasticsearch\Common\Exceptions\Missing404Exception;
use eLife\Search\Api\Elasticsearch\PlainElasticsearchClient;

final class ElasticsearchKeyValueStore implements KeyValueStore
{
    const INDEX_NAME = 'key_value_store'; // analogue to elife_search

    public function __construct(
        private PlainElasticsearchClient $client,
    ) {
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

    public function store(string $key, array $value)
    {
        $this->client->indexJsonDocument(
            $key,
            json_encode($value)
        );
    }

    public function load(string $key, $default = self::NO_DEFAULT) : array
    {
        try {
            return $this->client->getDocumentById(
                $key
            )['_source'];
        } catch (Missing404Exception $e) {
            if (self::NO_DEFAULT !== $default) {
                return $default;
            }
            // TODO: more abstract exception
            throw $e;
        }
    }
}
