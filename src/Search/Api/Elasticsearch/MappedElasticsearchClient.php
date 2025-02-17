<?php

namespace eLife\Search\Api\Elasticsearch;

use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use eLife\Search\Api\Query\QueryResponse;

class MappedElasticsearchClient
{
    private $libraryClient;
    private $indexDeterminer;
    private $forceSync;
    private $readClientOptions;

    public function __construct(Client $libraryClient, IndexDeterminer $indexDeterminer, bool $forceSync = false, array $readClientOptions = [])
    {
        $this->libraryClient = $libraryClient;
        $this->indexDeterminer = $indexDeterminer;
        $this->forceSync = $forceSync;
        $this->readClientOptions = $readClientOptions;
    }

    public function indexJsonDocument($id, $body, $flush = false)
    {
        $params = [
            'index' => $this->indexDeterminer->getCurrentIndexName(),
            'id' => $id,
            'body' => $body,
        ];

        $con = $this->libraryClient->index($params)['payload'] ?? null;
        if ($flush || $this->forceSync) {
            $this->libraryClient->indices()->refresh(['index' => $this->indexDeterminer->getCurrentIndexName()]);
        }

        return $con;
    }

    public function deleteDocument($id)
    {
        $params = [
            'index' => $this->indexDeterminer->getCurrentIndexName(),
            'id' => $id,
            'client' => ['ignore' => [400, 404]],
        ];

        return $this->libraryClient->delete($params)['payload'] ?? null;
    }

    public function searchDocuments(array $query): QueryResponse
    {
        $query['client'] = $this->readClientOptions;

        return $this->libraryClient->search($query)['payload'] ?? null;
    }

    public function getDocumentById($id, $index = null, $ignore404 = false)
    {
        $params = [
            'index' => $index ?? $this->indexDeterminer->getCurrentIndexName(),
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

    public function articleExists($id, array $types): bool
    {
        $params = [
            'index' => $this->indexDeterminer->getCurrentIndexName(),
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
