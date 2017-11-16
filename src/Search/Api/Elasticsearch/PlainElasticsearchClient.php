<?php

namespace eLife\Search\Api\Elasticsearch;

use Elasticsearch\Client;
use Throwable;

class PlainElasticsearchClient
{
    private $libraryClient;

    public function __construct(Client $libraryClient, string $index, bool $forceSync = false)
    {
        $this->libraryClient = $libraryClient;
        $this->index = $index;
        $this->forceSync = $forceSync;
    }

    public function defaultIndex(string $indexName)
    {
        $this->index = $indexName;
    }

    public function index() : string
    {
        return $this->index;
    }

    // plain
    public function deleteIndexByName(string $index)
    {
        $params = [
            'index' => $index,
            'client' => ['ignore' => [400, 404]],
        ];

        return $this->libraryClient->indices()->delete($params);
    }

    // plain, if used
    public function createIndex(string $indexName = null, $additionalParams = [])
    {
        $params = array_merge(
            [
                'index' => $indexName ?? $this->index,
            ],
            $additionalParams
        );

        return $this->libraryClient->indices()->create($params);
    }

    // plain
    public function deleteIndex(string $indexName = null)
    {
        $indexName = $indexName ?? $this->index;

        return $this->deleteIndexByName($indexName);
    }

    // plain
    public function indexExists(string $indexName = null)
    {
        try {
            // TODO: avoid using exceptions to check
            $this->libraryClient->indices()->getSettings([
                'index' => $indexName ?? $this->index,
            ]);

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    // plain
    public function customIndex($params)
    {
        $params['index'] = $this->index;

        $result = $this->libraryClient->indices()->create($params);
        $this->libraryClient->cluster()->health([
            'wait_for_status' => 'yellow', // 'green' would require replication
        ]);

        return $result;
    }

    public function indexJsonDocument($type, $id, $body, $flush = false, string $index = null)
    {
        $index = $index ?? $this->index;
        $params = [
            'index' => $index,
            'type' => $type,
            'id' => $id,
            'body' => $body,
        ];

        $con = $this->libraryClient->index($params)['payload'] ?? null;
        if ($flush || $this->forceSync) {
            $this->libraryClient->indices()->refresh(['index' => $this->index]);
        }

        return $con;
    }

    // plain
    public function getPlainDocumentById($type, $id, $index = null)
    {
        $params = [
            'index' => $index ?? $this->index,
            'type' => $type,
            'id' => $id,
        ];

        return $this->libraryClient->get($params);
    }
}
