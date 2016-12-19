<?php

namespace eLife\Search\Api\Elasticsearch;

use Elasticsearch\Client;
use eLife\Search\Api\Query\QueryResponse;
use eLife\Search\Api\Response\SearchResult;

class ElasticsearchClient
{
    private $connection;

    public function __construct(Client $connection, string $index)
    {
        $this->connection = $connection;
        $this->index = $index;
    }

    // TODO: Make these ACID like i.e Transactional

    public function deleteIndexByName($index)
    {
        $params = [
            'index' => $index,
            'client' => ['ignore' => [400, 404]],
        ];

        return $this->connection->indices()->delete($params);
    }

    public function deleteIndex()
    {
        return $this->deleteIndexByName($this->index);
    }

    public function indexExists($params = [])
    {
        $params['index'] = $this->index;

        return $this->connection->indices()->exists($params);
    }

    public function customIndex($params)
    {
        $params['index'] = $this->index;

        return $this->connection->indices()->create($params);
    }

    public function indexJsonDocument($type, $id, $body)
    {
        $params = [
            'index' => $this->index,
            'type' => $type,
            'id' => $id,
            'body' => $body,
        ];

        return $this->connection->index($params)['payload'] ?? null;
    }

    public function indexDocument($type, $id, SearchResult $body)
    {
        return $this->indexJsonDocument($type, $id, $body);
    }

    public function updateDocument()
    {
    }

    public function deleteDocument($type, $id)
    {
        $params = [
            'index' => $this->index,
            'type' => $type,
            'id' => $id,
            'client' => ['ignore' => [400, 404]],
        ];

        return $this->connection->delete($params)['payload'] ?? null;
    }

    public function searchDocuments($query) : QueryResponse
    {
        return $this->connection->search($query)['payload'] ?? null;
    }

    public function getDocumentById($type, $id)
    {
        $params = [
            'index' => $this->index,
            'type' => $type,
            'id' => $id,
        ];

        return $this->connection->get($params)['payload'] ?? null;
    }
}
