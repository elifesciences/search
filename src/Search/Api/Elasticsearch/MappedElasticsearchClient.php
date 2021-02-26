<?php

namespace eLife\Search\Api\Elasticsearch;

use Elasticsearch\Client;
use eLife\Search\Api\Query\QueryResponse;
use eLife\Search\Workflow\Blocks;

class MappedElasticsearchClient
{
    use Blocks;

    private $libraryClient;
    private $index;
    private $forceSync;
    private $readClientOptions;

    public function __construct(Client $libraryClient, string $index, bool $forceSync = false, array $readClientOptions = [])
    {
        $this->libraryClient = $libraryClient;
        $this->index = $index;
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

    public function indexJsonDocument($type, $id, $body, $flush = false, string $index = null)
    {
        $index = $index ?? $this->index;
        $params = [
            'index' => $index,
            'id' => "$type-$id",
            'body' => $this->prepareBody($body),
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
            'id' => "$type-$id",
            'client' => ['ignore' => [400, 404]],
        ];

        return $this->libraryClient->delete($params)['payload'] ?? null;
    }

    public function searchDocuments(array $query) : QueryResponse
    {
        $query['client'] = $this->readClientOptions;

        return $this->libraryClient->search($query)['payload'] ?? null;
    }

    public function getDocumentById($type, $id, $index = null)
    {
        $params = [
            'index' => $index ?? $this->index,
            'id' => "$type-$id",
        ];
        $params['client'] = $this->readClientOptions;

        return $this->libraryClient->get($params)['payload'] ?? null;
    }

    private function prepareBody($body)
    {
        $json = json_decode(json_encode($body));
        if (in_array($json->type, ['blog-article', 'interview', 'labs-post'])) {
            $json->body = strip_tags($this->flattenBlocks($json->content ?? []));
            unset($json->content);
        }

        if ('collection' === $json->type) {
            $json->summary = strip_tags($this->flattenBlocks($json->summary ?? []));
        }

        if (!in_array($json->type, ['blog-article', 'collection', 'interview', 'labs-post', 'podcast-episode'])) {
            $json->body = strip_tags($this->flattenBlocks($json->body ?? []));
            $json->abstract = strip_tags($this->flattenBlocks($json->abstract->content ?? []));
            // Fix author name.
            $json->authors = array_map(function ($author) {
                if (is_string($author->name)) {
                    $author->name = ['value' => $author->name];
                }

                return $author;
            }, $json->authors ?? []);
            // Fix author name in references.
            $json->references = array_map(function ($reference) {
                $reference->authors = array_map(function ($author) {
                    if (is_string($author->name)) {
                        $author->name = ['value' => $author->name];
                    }

                    return $author;
                }, $reference->authors ?? []);

                unset($reference->pages);
                return $reference;
            }, $json->references ?? []);
            $json->appendices = array_map(function ($appendix) {
                return strip_tags($this->flattenBlocks($appendix->content ?? []));
            }, $json->appendices ?? []);
            // Completely serialize funding
            $json->acknowledgements = $this->flattenBlocks($json->acknowledgements ?? []);
            $json->decisionLetter = $this->flattenBlocks($json->decisionLetter->content ?? []);
            $json->authorResponse = $this->flattenBlocks($json->authorResponse->content ?? []);
            $json->funding = [
                'format' => 'json',
                'value' => json_encode($json->funding ?? '[]'),
            ];
            // Completely serialize dataSets
            $json->dataSets = [
                'format' => 'json',
                'value' => json_encode($json->dataSets ?? '[]'),
            ];
        }

        return json_decode(json_encode($json), true);
    }
}
