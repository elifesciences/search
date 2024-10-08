<?php

namespace eLife\Search\Indexer\ModelIndexer;

use eLife\ApiSdk\Model\PodcastEpisode;
use eLife\ApiSdk\Model\Model;
use eLife\Search\Indexer\ChangeSet;

final class PodcastEpisodeIndexer extends AbstractModelIndexer
{
    protected function getSdkClass(): string
    {
        return PodcastEpisode::class;
    }

    /**
     * @param PodcastEpisode $podcastEpisode
     * @return ChangeSet
     */
    public function prepareChangeSet(Model $podcastEpisode) : ChangeSet
    {
        $changeSet = new ChangeSet();

        // Normalized fields.
        $podcastEpisodeObject = json_decode($this->serialize($podcastEpisode));
        $podcastEpisodeObject->type = 'podcast-episode';
        $podcastEpisodeObject->snippet = ['format' => 'json', 'value' => json_encode($this->snippet($podcastEpisode))];
        // Add sort date.
        $this->addSortDate($podcastEpisodeObject, $podcastEpisode->getPublishedDate());


        $changeSet->addInsert(
            $podcastEpisodeObject->type.'-'.$podcastEpisode->getNumber(),
            json_encode($podcastEpisodeObject),
        );
        return $changeSet;
    }
}
