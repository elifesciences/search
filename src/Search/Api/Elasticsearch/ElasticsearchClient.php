<?php

namespace eLife\Search\Api\Elasticsearch;

use Elasticsearch\Client;
use eLife\Search\Api\Response\SearchResult;

class ElasticsearchClient
{
    private $connection;

    public function __construct(Client $connection)
    {
        $this->connection = $connection;
    }

    // TODO: Make these ACID like i.e Transactional

    public function deleteIndexByName($indexName)
    {
        $params = ['index' => $indexName];

        return $this->connection->indices()->delete($params);
    }

    public function createIndex($indexName)
    {
        $params = ['index' => $indexName];

        return $this->connection->indices()->create($params);
    }

    public function indexDocument($indexName, $type, $id, SearchResult $body)
    {
        $params = ['index' => $indexName,
            'type' => $type,
            'id' => $id,
            'body' => $body, ];

        return $this->connection->index($params);
    }

    public function updateDocument()
    {
    }

    public function deleteDocument($indexName, $type, $id)
    {
        $params = ['index' => $indexName,
            'type' => $type,
            'id' => $id, ];

        return $this->connection->delete($params);
    }

    public function searchDocuments($indexName, $type, $body)
    {
        $params = ['index' => $indexName, 'type' => $type, 'body' => $body];

        return $this->connection->search($params);
    }

    public function getDocumentById($indexName, $type, $id)
    {
        $params = ['index' => $indexName, 'type' => $type, 'id' => $id];

        return $this->connection->get($params);
    }
}
