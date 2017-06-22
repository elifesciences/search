<?php

namespace eLife\Search\Api\Elasticsearch;

use Elasticsearch\Client;
use eLife\Search\Api\Query\QueryResponse;
use eLife\Search\Api\Response\SearchResult;
use Throwable;

class ElasticsearchClient
{
    private $connection;

    public function __construct(Client $connection, string $index, bool $forceSync = false)
    {
        $this->connection = $connection;
        $this->index = $index;
        $this->forceSync = $forceSync;
    }

    public function defaultIndex(string $indexName)
    {
        $this->index = $indexName;
    }

    public function deleteIndexByName(string $index)
    {
        $params = [
            'index' => $index,
            'client' => ['ignore' => [400, 404]],
        ];

        return $this->connection->indices()->delete($params);
    }

    public function createIndex(string $indexName){

        $params = [
            'index' => $indexName

        ];
        return $this->connection->indices()->create($params);

    }

    public function deleteIndex(string $indexName = null)
    {
       $indexName = $indexName ?? $this->index;
        return $this->deleteIndexByName($indexName);
    }

    public function indexExists()
    {
        try {
            $this->connection->indices()->getSettings([
                'index' => $this->index,
            ]);

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function customIndex($params)
    {
        $params['index'] = $this->index;

        return $this->connection->indices()->create($params);
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

        $con = $this->connection->index($params)['payload'] ?? null;
        if ($flush || $this->forceSync) {
            $this->connection->indices()->refresh(['index' => $this->index]);
        }

        return $con;
    }

    public function indexDocument($type, $id, SearchResult $body)
    {
        return $this->indexJsonDocument($type, $id, $body);
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

    public function count($index){

        $params= [
            'index'=>$index,
        ];

        return $this->connection->count($params);

    }

    public function moveIndex(string $source, string $destination){


        $params = [
            'body' => [
                'source' => [
                    'index'  => $source
                ],
                'dest' => [
                    'index' => $destination
                ]
            ]
        ];

        return $this->connection->reindex($params);

    }
}
