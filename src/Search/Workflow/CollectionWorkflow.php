<?php

namespace eLife\Search\Workflow;

use Assert\Assertion;
use eLife\ApiSdk\Model\Collection;
use eLife\ApiSdk\Model\Model;
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
    public function index(Model $collection) : array
    {
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

    public function getSdkClass() : string
    {
        return Collection::class;
    }
}
