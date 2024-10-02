<?php

namespace eLife\Search\Workflow;

use Throwable;
use Assert\Assertion;
use Psr\Log\LoggerInterface;
use eLife\ApiSdk\Model\Model;
use eLife\Search\Api\ApiValidator;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use eLife\Search\Api\Elasticsearch\Response\DocumentResponse;
use Symfony\Component\Serializer\Serializer;

abstract class AbstractWorkflow
{
    protected LoggerInterface $logger;
    protected MappedElasticsearchClient $client;
    protected ApiValidator $validator;
    protected Serializer $serializer;

    public function __construct(
        Serializer $serializer,
        LoggerInterface $logger,
        MappedElasticsearchClient $client,
        ApiValidator $validator
    ) {
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->client = $client;
        $this->validator = $validator;
    }

    abstract public function index(Model $entity);

    public function insert(string $json, string $id)
    {
        // Insert the document.
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
        $debugId = '<'.$entity->getIdentifier().'>';

        $result = $this->index($entity);

        if ($result['skipInsert']) {
            $this->logger->debug($debugId.' skipping indexing.');
            return;
        }
        $this->logger->debug($debugId.' indexing '.$entity->getTitle());

        $doc = $result['json'];
        $docId = $result['id'];
        $this->logger->debug($debugId.' importing into Elasticsearch.');
        $this->insert($doc, $docId);

        $this->logger->debug($debugId.' post validating.');
        try {
            $this->postValidate($docId);
        } catch (Throwable $e) {
            $this->logger->error($debugId.' rolling back.', [
                'exception' => $e,
                'document' => $result ?? null,
            ]);
            $this->client->deleteDocument($docId);

            // We failed.
            throw new \Exception($debugId.' post validate failed.');
        }

        $this->logger->info($debugId.' successfully imported.');

        return [
            'id' => $docId,
        ];
    }
}
