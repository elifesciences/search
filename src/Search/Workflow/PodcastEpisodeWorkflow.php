<?php

namespace eLife\Search\Workflow;

use Assert\Assertion;
use eLife\ApiSdk\Model\Model;
use eLife\ApiSdk\Model\PodcastEpisode;
use eLife\Search\Api\ApiValidator;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use eLife\Search\Api\Elasticsearch\Response\DocumentResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;
use Throwable;

final class PodcastEpisodeWorkflow extends AbstractWorkflow
{
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
        $this->client = $client;
        $this->logger = $logger;
        $this->validator = $validator;
    }

    /**
     * @param PodcastEpisode $podcastEpisode
     * @return array
     */
    public function index(Model $podcastEpisode) : array
    {
        $this->logger->debug('indexing '.$podcastEpisode->getTitle());

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
