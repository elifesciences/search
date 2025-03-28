<?php

namespace eLife\Search\Indexer\ModelIndexer;

use eLife\ApiSdk\Model\ReviewedPreprint;
use eLife\ApiSdk\Model\Model;
use eLife\Search\Indexer\ChangeSet;
use Symfony\Component\Serializer\Serializer;

final class ReviewedPreprintIndexer extends AbstractModelIndexer
{
    private ReviewedPreprintLifecycle $reviewedPreprintLifecycle;

    public function __construct(Serializer $serializer, ReviewedPreprintLifecycle $reviewedPreprintLifecycle)
    {
        parent::__construct($serializer);
        $this->reviewedPreprintLifecycle = $reviewedPreprintLifecycle;
    }

    protected function getSdkClass(): string
    {
        return ReviewedPreprint::class;
    }

    /**
     * @param ReviewedPreprint $reviewedPreprint
     * @return ChangeSet
     */
    public function prepareChangeSet(Model $reviewedPreprint) : ChangeSet
    {
        $changeSet = new ChangeSet();
        if ($this->reviewedPreprintLifecycle->isSuperseded($reviewedPreprint->getId())) {
            return $changeSet;
        }

        $reviewedPreprintObject = json_decode($this->serialize($reviewedPreprint));
        $reviewedPreprintObject->type = 'reviewed-preprint';
        $reviewedPreprintObject->body = $reviewedPreprint->getIndexContent() ?? '';
        $reviewedPreprintObject->snippet = ['format' => 'json', 'value' => json_encode($this->snippet($reviewedPreprint))];

        $this->addSortDate($reviewedPreprintObject, $reviewedPreprint->getStatusDate());

        $changeSet->addInsert(
            $reviewedPreprintObject->type.'-'.$reviewedPreprint->getId(),
            json_encode($reviewedPreprintObject),
        );
        return $changeSet;
    }
}
