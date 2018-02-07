<?php

namespace eLife\Search\Api\Elasticsearch;

use Elasticsearch\Client;
use eLife\Search\Api\Query\QueryResponse;

class MappedElasticsearchClient
{
    private $libraryClient;

    public function __construct(Client $libraryClient, string $index, bool $forceSync = false, array $clientOptions = [])
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

    public function deleteDocument($type, $id)
    {
        $params = [
            'index' => $this->index,
            'type' => $type,
            'id' => $id,
            'client' => ['ignore' => [400, 404]],
        ];

        return $this->libraryClient->delete($params)['payload'] ?? null;
    }

    public function searchDocuments(array $query) : QueryResponse
    {
        return $this->libraryClient->search($query)['payload'] ?? null;
    }

    public function getDocumentById($type, $id, $index = null)
    {
        $params = [
            'index' => $index ?? $this->index,
            'type' => $type,
            'id' => $id,
        ];

        return $this->libraryClient->get($params)['payload'] ?? null;
    }
}
