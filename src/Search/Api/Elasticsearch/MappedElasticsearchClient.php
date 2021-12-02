<?php

namespace eLife\Search\Api\Elasticsearch;

use Elasticsearch\Client;
use eLife\Search\Api\Query\QueryResponse;
use Psr\Log\LoggerInterface;

class MappedElasticsearchClient
{
    private $libraryClient;
    private $index;
    private $logger;
    private $forceSync;
    private $readClientOptions;

    public function __construct(
        Client $libraryClient,
        string $index,
        LoggerInterface $logger,
        bool $forceSync = false,
        array $readClientOptions = []
    )
    {
        $this->libraryClient = $libraryClient;
        $this->index = $index;
        $this->logger = $logger;
        $this->forceSync = $forceSync;
        $this->readClientOptions = $readClientOptions;
    }

    public function defaultIndex(string $indexName)
    {
        $this->index = $indexName;
    }

    public function index() : string
    {
        return $this->index;
    }

    public function indexJsonDocument($id, $body, $flush = false, string $index = null)
    {
        $index = $index ?? $this->index;
        $params = [
            'index' => $index,
            'id' => $id,
            'body' => $body,
        ];

        $this->logger->info("Index document $id:", [
            'data' => $params,
        ]);
        $index = $this->libraryClient->index($params);
        if ($flush || $this->forceSync) {
            $this->libraryClient->indices()->refresh(['index' => $this->index]);
        }

        return $index;
    }

    public function deleteDocument($id)
    {
        $params = [
            'index' => $this->index,
            'id' => $id,
            'client' => ['ignore' => [400, 404]],
        ];

        return $this->libraryClient->delete($params)['payload'] ?? null;
    }

    public function searchDocuments(array $query) : QueryResponse
    {
        $query['client'] = $this->readClientOptions;

        return $this->libraryClient->search($query)['payload'] ?? null;
    }

    public function getDocumentById($id, $index = null)
    {
        $params = [
            'index' => $index ?? $this->index,
            'id' => $id,
        ];
        $params['client'] = $this->readClientOptions;

        return $this->libraryClient->get($params)['payload'] ?? null;
    }
}
