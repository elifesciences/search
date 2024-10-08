<?php

namespace eLife\Search\Indexer\ModelIndexer;

use eLife\ApiSdk\Model\Interview;
use eLife\ApiSdk\Model\Model;
use eLife\Search\Indexer\ChangeSet;

final class InterviewIndexer extends AbstractModelIndexer
{
    protected function getSdkClass(): string
    {
        return Interview::class;
    }

    /**
     * @param Interview $interview
     * @return ChangeSet
     */
    public function prepareChangeSet(Model $interview) : ChangeSet
    {
        $changeSet = new ChangeSet();

        // Normalized fields.
        $interviewObject = json_decode($this->serialize($interview));
        $interviewObject->type = 'interview';
        $interviewObject->body = $this->flattenBlocks($interviewObject->content ?? []);
        unset($interviewObject->content);
        $interviewObject->snippet = ['format' => 'json', 'value' => json_encode($this->snippet($interview))];
        // Add publish date to sort on.
        $this->addSortDate($interviewObject, $interview->getPublishedDate());


        $changeSet->addInsert(
            $interviewObject->type.'-'.$interview->getId(),
            json_encode($interviewObject),
        );
        return $changeSet;
    }
}
