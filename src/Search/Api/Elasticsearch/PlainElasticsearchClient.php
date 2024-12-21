<?php

namespace eLife\Search\Api\Elasticsearch;

use Elasticsearch\Client;

class PlainElasticsearchClient
{
    private Client $libraryClient;

    private string $index;

    public function __construct(Client $libraryClient, string $index)
    {
        $this->libraryClient = $libraryClient;
        $this->index = $index;
    }

    public function defaultIndex(string $indexName): void
    {
        $this->index = $indexName;
    }

    public function index() : string
    {
        return $this->index;
    }

    /** @return array<string> */
    public function allIndexes() : array
    {
        $indexes = $this->libraryClient->cat()->indices(['h' => 'index']);
        return array_map(function($item) {
            return trim($item['index']);
        }, $indexes);
    }

    public function createIndex(string $indexName = null, mixed $additionalParams = []): void
    {
        $params = array_merge(
            [
                'index' => $indexName ?? $this->index,
            ],
            $additionalParams
        );

        $this->libraryClient->indices()->create($params);
        $this->libraryClient->cluster()->health([
            'wait_for_status' => 'yellow', // 'green' would require replication
        ]);
    }

    public function deleteIndex(string $indexName = null): void
    {
        $indexName = $indexName ?? $this->index;

        $params = [
            'index' => $indexName,
            'client' => ['ignore' => [400, 404]],
        ];

        $this->libraryClient->indices()->delete($params);
    }

    public function indexCount(string $indexName = null): int
    {
        return $this->libraryClient->count([
            'index' => $indexName ?? $this->index,
        ])['count'];
    }

    public function indexExists(string $indexName = null): bool
    {
        return $this->libraryClient->indices()->exists([
            'index' => $indexName ?? $this->index,
        ]);
    }

    public function indexJsonDocument(string $id, mixed $body): mixed
    {
        $index = $this->index;
        $params = [
            'index' => $index,
            'id' => $id,
            'body' => $body,
        ];

        $con = $this->libraryClient->index($params)['payload'] ?? null;
        $this->libraryClient->indices()->refresh(['index' => $this->index]);

        return $con;
    }

    public function getDocumentById(string $id, string|null $index = null): mixed
    {
        $params = [
            'index' => $index ?? $this->index,
            'id' => $id,
        ];

        return $this->libraryClient->get($params);
    }
}
