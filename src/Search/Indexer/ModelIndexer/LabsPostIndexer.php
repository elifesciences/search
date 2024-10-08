<?php

namespace eLife\Search\Indexer\ModelIndexer;

use eLife\ApiSdk\Model\LabsPost;
use eLife\ApiSdk\Model\Model;
use eLife\Search\Indexer\ChangeSet;

final class LabsPostIndexer extends AbstractModelIndexer
{
    protected function getSdkClass(): string
    {
        return LabsPost::class;
    }

    /**
     * @param LabsPost $labsPost
     * @return ChangeSet
     */
    public function prepareChangeSet(Model $labsPost) : ChangeSet
    {
        $changeSet = new ChangeSet();

        // Normalized fields.
        $labsPostObject = json_decode($this->serialize($labsPost));
        $labsPostObject->type = 'labs-post';
        $labsPostObject->body = $this->flattenBlocks($labsPostObject->content ?? []);
        unset($labsPostObject->content);
        $labsPostObject->snippet = ['format' => 'json', 'value' => json_encode($this->snippet($labsPost))];
        $this->addSortDate($labsPostObject, $labsPost->getPublishedDate());


        $changeSet->addInsert(
            $labsPostObject->type.'-'.$labsPost->getId(),
            json_encode($labsPostObject),
        );
        return $changeSet;
    }
}
