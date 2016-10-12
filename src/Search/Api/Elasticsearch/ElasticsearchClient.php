<?php

namespace eLife\Search\Api\Elasticsearch;

use Elasticsearch\Client;
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

    public function createIndex()
    {
        $params = [
            'index' => $this->index,
            'client' => ['ignore' => [400, 404]],
        ];

        return $this->connection->indices()->create($params);
    }

    public function indexDocument($type, $id, SearchResult $body)
    {
        $params = [
            'index' => $this->index,
            'type' => $type,
            'id' => $id,
            'body' => $body,
        ];

        return $this->connection->index($params);
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

        return $this->connection->delete($params);
    }

    public function searchDocuments($type, $body)
    {
        $params = [
            'index' => $this->index,
            'type' => $type,
            'body' => $body,
        ];

        return $this->connection->search($params);
    }

    public function getDocumentById($type, $id)
    {
        $params = [
            'index' => $this->index,
            'type' => $type,
            'id' => $id,
        ];

        return $this->connection->get($params);
    }
}
