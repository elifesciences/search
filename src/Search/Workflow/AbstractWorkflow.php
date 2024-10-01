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
        // Post-validation, we got a document.
        $document = $this->client->getDocumentById($id);
        Assertion::isInstanceOf($document, DocumentResponse::class);
        $result = $document->unwrap();
        // That the document is valid JSON.
        $this->validator->validateSearchResult($result, true);
    }

    public function run($entity) {
        $result = $this->index($entity);
        $docId = $result['id'];

        if ($result['skipInsert']) {
            $this->logger->debug($docId.' skipping indexing.');
            return;
        }
        $doc = $result['json'];

        $this->insert($doc, $docId);
        try {
            $this->postValidate($docId);
        } catch (Throwable $e) {
            $this->logger->error($docId.' rolling back.', [
                'exception' => $e,
                'document' => $result ?? null,
            ]);
            $this->client->deleteDocument($docId);

            // We failed.
            throw new \Exception("Post validate failed.");
        }

        $this->logger->info($docId.' successfully imported.');

        return [
            'id' => $docId,
        ];
    }
}
