<?php

namespace eLife\Search\Api\Elasticsearch;

use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use eLife\Search\Api\Elasticsearch\Response\ElasticResponse;
use eLife\Search\Api\Elasticsearch\Response\ErrorResponse;
use eLife\Search\Api\Elasticsearch\Response\SearchResponse;

class MappedElasticsearchClient
{
    /**
     * @param array<string, mixed> $readClientOptions
     */
    public function __construct(
        private Client $libraryClient,
        private string $index,
        private bool $forceSync = false,
        private array $readClientOptions = []
    ) {
    }

    public function defaultIndex(string $indexName): void
    {
        $this->index = $indexName;
    }

    public function index(): string
    {
        return $this->index;
    }

    public function indexJsonDocument(string $id, string $body, bool $flush = false, string $index = null): ElasticResponse
    {
        $index = $index ?? $this->index;
        $params = [
            'index' => $index,
            'id' => $id,
            'body' => $body,
        ];

        $con = $this->libraryClient->index($params)['payload'] ?? null;
        if ($flush || $this->forceSync) {
            $this->libraryClient->indices()->refresh(['index' => $this->index]);
        }

        return $con;
    }

    /**
     * @return array<string, mixed>
     */
    public function deleteDocument(string $id): array
    {
        $params = [
            'index' => $this->index,
            'id' => $id,
            'client' => ['ignore' => [400, 404]],
        ];

        return $this->libraryClient->delete($params)['payload'] ?? null;
    }

    /**
     * @param array<string,mixed> $query
     */
    public function searchDocuments(array $query): SearchResponse|ErrorResponse|null
    {
        $query['client'] = $this->readClientOptions;

        return $this->libraryClient->search($query)['payload'] ?? null;
    }

    public function getDocumentById(string $id, string|null $index = null, bool $ignore404 = false): ElasticResponse|null
    {
        $params = [
            'index' => $index ?? $this->index,
            'id' => $id,
        ];
        $params['client'] = $this->readClientOptions;

        try {
            return $this->libraryClient->get($params)['payload'] ?? null;
        } catch (Missing404Exception $e) {
            if ($ignore404) {
                return null;
            }

            throw $e;
        }
    }

    /**
     * @param array<int,mixed> $types
     */
    public function articleExists(string $id, array $types): bool
    {
        $params = [
            'index' => $this->index,
            'body'  => [
                'query' => [
                    'bool' => [
                        'must' => [
                            ['terms' => ['type.keyword' => $types]],
                            ['term' => ['id' => $id]]
                        ]
                    ]
                ],
                'size' => 1
            ]
        ];
        $response = $this->searchDocuments($params);
        return count($response->toArray()) > 0;
    }
}
