<?php

namespace eLife\Search\Workflow;

use Assert\Assertion;
use eLife\ApiSdk\Model\Collection;
use eLife\Search\Annotation\GearmanTask;
use eLife\Search\Api\ApiValidator;
use eLife\Search\Api\Elasticsearch\ElasticsearchClient;
use eLife\Search\Api\Elasticsearch\Response\DocumentResponse;
use eLife\Search\Api\Response\CollectionResponse;
use eLife\Search\Gearman\InvalidWorkflow;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;
use Throwable;

final class CollectionWorkflow implements Workflow
{
    const WORKFLOW_SUCCESS = 1;
    const WORKFLOW_FAILURE = -1;

    use JsonSerializeTransport;

    /**
     * @var Serializer
     */
    private $serializer;
    private $logger;
    private $client;
    private $validator;

    public function __construct(Serializer $serializer, LoggerInterface $logger, ElasticsearchClient $client, ApiValidator $validator)
    {
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->client = $client;
        $this->validator = $validator;
    }

    /**
     * @GearmanTask(
     *     name="collection_validate",
     *     next="collection_index",
     *     deserialize="deserialize",
     *     serialize="serialize"
     * )
     * @SuppressWarnings(ForbiddenDateTime)
     */
    public function validate(Collection $collection) : Collection
    {
        // Create response to validate.
        $searchCollection = $this->validator->deserialize($this->serialize($collection), CollectionResponse::class);
        // Validate that response.
        $isValid = $this->validator->validateSearchResult($searchCollection);
        if ($isValid === false) {
            $this->logger->alert($this->validator->getLastError()->getMessage());
            throw new InvalidWorkflow('Collection<'.$collection->getId().'> Invalid item tried to be imported.');
        }
        // Log results.
        $this->logger->info('Collection<'.$collection->getId().'> validated against current schema.');
        // Pass it on.
        return $collection;
    }

    /**
     * @GearmanTask(
     *     name="collection_index",
     *     next="collection_insert",
     *     deserialize="deserialize"
     * )
     */
    public function index(Collection $collection) : array
    {
        // This step is still not used very much.
        $this->logger->debug('Collection<'.$collection->getId().'> Indexing '.$collection->getTitle());
        // Return.
        return [
            'json' => $this->serialize($collection),
            'type' => 'collection',
            'id' => $collection->getId(),
        ];
    }

    /**
     * @GearmanTask(name="collection_insert", next="collection_post_validate", parameters={"json", "type", "id"})
     */
    public function insert(string $json, string $type, string $id) : array
    {
        // Insert the document.
        $this->logger->debug('Collection<'.$id.'> importing into Elasticsearch.');
        $this->client->indexJsonDocument($type, $id, $json);

        return [
            'type' => $type,
            'id' => $id,
        ];
    }

    /**
     * @GearmanTask(name="collection_post_validate", parameters={"type", "id"})
     */
    public function postValidate(string $type, string $id) : int
    {
        try {
            // Post-validation, we got a document.
            $document = $this->client->getDocumentById($type, $id);
            Assertion::isInstanceOf($document, DocumentResponse::class);
            $result = $document->unwrap();
            // That document contains a collection.
            Assertion::isInstanceOf($result, CollectionResponse::class);
            // That collection is valid JSON.
            $this->validator->validateSearchResult($result, true);
        } catch (Throwable $e) {
            $this->logger->alert($e->getMessage());
            $this->logger->alert('Collection<'.$id.'> rolling back');
            $this->client->deleteDocument($type, $id);
            // We failed.
            return self::WORKFLOW_FAILURE;
        }

        $this->logger->info('Collection<'.$id.'> successfully imported.');

        return self::WORKFLOW_SUCCESS;
    }

    public function getSdkClass() : string
    {
        return Collection::class;
    }
}
