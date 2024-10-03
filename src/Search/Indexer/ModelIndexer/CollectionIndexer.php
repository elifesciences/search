<?php

namespace eLife\Search\Indexer\ModelIndexer;

use eLife\ApiSdk\Model\Collection;
use eLife\ApiSdk\Model\Model;
use eLife\Search\Indexer\ChangeSet;

final class CollectionIndexer extends AbstractModelIndexer
{
    protected function getSdkClass(): string
    {
        return Collection::class;
    }

    /**
     * @param Collection $collection
     * @return ChangeSet
     */
    public function prepareChangeSet(Model $collection) : ChangeSet
    {
        $changeSet = new ChangeSet();

        // Normalized fields.
        $collectionObject = json_decode($this->serialize($collection));
        $collectionObject->type = 'collection';
        $collectionObject->summary = $this->flattenBlocks($collectionObject->summary ?? []);
        $collectionObject->snippet = ['format' => 'json', 'value' => json_encode($this->snippet($collection))];
        $this->addSortDate($collectionObject, $collection->getPublishedDate());


        $changeSet->addInsert(
            $collectionObject->type.'-'.$collection->getId(),
            json_encode($collectionObject),
        );
        return $changeSet;
    }
}
