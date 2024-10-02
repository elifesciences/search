<?php

namespace eLife\Search\Workflow;

use eLife\ApiSdk\Model\Model;
use eLife\ApiSdk\Model\PodcastEpisode;
use eLife\Search\Api\ApiValidator;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;

final class PodcastEpisodeWorkflow extends AbstractWorkflow
{
    use JsonSerializeTransport;
    use SortDate;

    /**
     * @param PodcastEpisode $podcastEpisode
     * @return array
     */
    public function prepare(Model $podcastEpisode) : array
    {
        // Normalized fields.
        $podcastEpisodeObject = json_decode($this->serialize($podcastEpisode));
        $podcastEpisodeObject->type = 'podcast-episode';
        $podcastEpisodeObject->snippet = ['format' => 'json', 'value' => json_encode($this->snippet($podcastEpisode))];
        // Add sort date.
        $this->addSortDate($podcastEpisodeObject, $podcastEpisode->getPublishedDate());

        return [
            'json' => json_encode($podcastEpisodeObject),
            'id' => $podcastEpisodeObject->type.'-'.$podcastEpisode->getNumber(),
        ];
    }

    public function getSdkClass() : string
    {
        return PodcastEpisode::class;
    }
}
