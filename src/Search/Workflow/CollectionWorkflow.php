<?php

namespace eLife\Search\Workflow;

use eLife\ApiSdk\Model\Collection;
use eLife\ApiSdk\Model\Model;
use eLife\Search\Api\ApiValidator;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;

final class CollectionWorkflow extends AbstractWorkflow
{
    use Blocks;
    use JsonSerializeTransport;
    use SortDate;

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
