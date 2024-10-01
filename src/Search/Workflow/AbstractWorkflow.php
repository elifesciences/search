<?php

namespace eLife\Search\Workflow;

use Throwable;
use Assert\Assertion;
use Psr\Log\LoggerInterface;
use eLife\ApiSdk\Model\Model;
use eLife\Search\Api\ApiValidator;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use eLife\Search\Api\Elasticsearch\Response\DocumentResponse;

abstract class AbstractWorkflow
{
    protected LoggerInterface $logger;
    protected MappedElasticsearchClient $client;
    protected ApiValidator $validator;

    abstract public function index(Model $entity);

    public function insert(string $json, string $id)
    {
        // Insert the document.
        $this->logger->debug($id.' importing into Elasticsearch.');
        $this->client->indexJsonDocument($id, $json);
        return [
            'id' => $id,
        ];
    }

    public function postValidate($id)
    {
        try {
            // Post-validation, we got a document.
            $document = $this->client->getDocumentById($id);
            Assertion::isInstanceOf($document, DocumentResponse::class);
            $result = $document->unwrap();
            // That podcast episode is valid JSON.
            $this->validator->validateSearchResult($result, true);
        } catch (Throwable $e) {
            $this->logger->error($id.' rolling back.', [
                'exception' => $e,
                'document' => $result ?? null,
            ]);
            $this->client->deleteDocument($id);

            // We failed.
            throw new \Exception("Post validate failed.");
        }

        $this->logger->info($id.' successfully imported.');

        return [
            'id' => $id,
        ];
    }

    public function run($entity) {
        $result = $this->index($entity);
        if ($result['skipInsert']) {
            $this->logger->debug($result['id'].' skipping indexing.');
            return;
        }
        $this->insert($result['json'], $result['id']);
        $this->postValidate($result['id']);
    }
}
