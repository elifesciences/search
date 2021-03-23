<?php

namespace eLife\Search\Workflow;

use Assert\Assertion;
use eLife\ApiSdk\Model\Collection;
use eLife\Search\Annotation\GearmanTask;
use eLife\Search\Api\ApiValidator;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use eLife\Search\Api\Elasticsearch\Response\DocumentResponse;
use eLife\Search\Api\Response\CollectionResponse;
use eLife\Search\Gearman\InvalidWorkflow;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;
use Throwable;

final class CollectionWorkflow implements Workflow
{
    use JsonSerializeTransport;
    use SortDate;

    const WORKFLOW_SUCCESS = 1;
    const WORKFLOW_FAILURE = -1;

    /**
     * @var Serializer
     */
    private $serializer;
    private $logger;
    private $client;
    private $validator;

    public function __construct(Serializer $serializer, LoggerInterface $logger, MappedElasticsearchClient $client, ApiValidator $validator)
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
        if (false === $isValid) {
            $this->logger->error(
                'Collection<'.$collection->getId().'> cannot be transformed into a valid search result',
                [
                    'input' => [
                        'type' => 'collection',
                        'id' => $collection->getId(),
                    ],
                    'search_result' => $this->validator->serialize($searchCollection),
                    'validation_error' => $this->validator->getLastError()->getMessage(),
                ]
            );
            throw new InvalidWorkflow('Collection<'.$collection->getId().'> cannot be trasformed into a valid search result. See error log for details.');
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
        $this->logger->debug('Collection<'.$collection->getId().'> Indexing '.$collection->getTitle());
        // Normalized fields.
        $collectionObject = json_decode($this->serialize($collection));
        $collectionObject->snippet = $this->snippet($collection);
        $this->addSortDate($collectionObject, $collection->getPublishedDate());

        // Return.
        return [
            'json' => json_encode($collectionObject),
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
            $this->logger->error('Collection<'.$id.'> rolling back', [
                'exception' => $e,
            ]);
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
