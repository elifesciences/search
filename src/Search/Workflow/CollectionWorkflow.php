<?php

namespace eLife\Search\Workflow;

use Assert\Assertion;
use eLife\ApiSdk\Model\Collection;
use eLife\Search\Api\ApiValidator;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use eLife\Search\Api\Elasticsearch\Response\DocumentResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;
use Throwable;

final class CollectionWorkflow extends AbstractWorkflow
{
    use Blocks;
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
     * @param Collection $collection
     * @return array
     */
    public function index($collection) : array
    {
        $this->logger->debug('Collection<'.$collection->getId().'> Indexing '.$collection->getTitle());
        // Normalized fields.
        $collectionObject = json_decode($this->serialize($collection));
        $collectionObject->type = 'collection';
        $collectionObject->summary = $this->flattenBlocks($collectionObject->summary ?? []);
        $collectionObject->snippet = ['format' => 'json', 'value' => json_encode($this->snippet($collection))];
        $this->addSortDate($collectionObject, $collection->getPublishedDate());

        // Return.
        return [
            'json' => json_encode($collectionObject),
            'id' => $collectionObject->type.'-'.$collection->getId(),
        ];
    }

    public function insert(string $json, string $id) : array
    {
        // Insert the document.
        $this->logger->debug('Collection<'.$id.'> importing into Elasticsearch.');
        $this->client->indexJsonDocument($id, $json);

        return [
            'id' => $id,
        ];
    }

    public function postValidate(string $id) : int
    {
        try {
            // Post-validation, we got a document.
            $document = $this->client->getDocumentById($id);
            Assertion::isInstanceOf($document, DocumentResponse::class);
            $result = $document->unwrap();
            // That collection is valid JSON.
            $this->validator->validateSearchResult($result, true);
        } catch (Throwable $e) {
            $this->logger->error('Collection<'.$id.'> rolling back', [
                'exception' => $e,
            ]);
            $this->client->deleteDocument($id);

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
