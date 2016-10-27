<?php

namespace eLife\Search\Workflow;

use Assert\Assertion;
use eLife\ApiSdk\Model\PodcastEpisode;
use eLife\Search\Annotation\GearmanTask;
use eLife\Search\Api\ApiValidator;
use eLife\Search\Api\Elasticsearch\ElasticsearchClient;
use eLife\Search\Api\Elasticsearch\Response\DocumentResponse;
use eLife\Search\Api\Elasticsearch\Response\PodcastEpisodeRepsonse;
use eLife\Search\Gearman\InvalidWorkflow;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;
use Throwable;

final class PodcastEpisodeWorkflow implements Workflow
{
    const WORKFLOW_SUCCESS = 1;
    const WORKFLOW_FAILURE = -1;

    use JsonSerializeTransport;

    /**
     * @var Serializer
     */
    private $serializer;
    private $logger;
    private $client;
    private $validator;

    public function __construct(Serializer $serializer, LoggerInterface $logger, ElasticsearchClient $client)
    {
        $this->serializer = $serializer;
        $this->client = $client;
        $this->logger = $logger;
        $this->validator = $validator;

    }

    /**
     * @GearmanTask(
     *     name="podcast_episode_validate",
     *     next="podcast_episode_index",
     *     deserialize="deserialize",
     *     serialize="serialize"
     * )
     */
    public function validate(PodcastEpisode $podcastEpisode) : PodcastEpisode
    {
        $this->logger->debug('validating '.$podcastEpisode->getTitle());
        return $podcastEpisode;
    }

    /**
     * @GearmanTask(
     *     name="podcast_episode_index",
     *     next="podcast_episode_insert",
     *     deserialize="deserialize"
     * )
     */
    public function index(PodcastEpisode $podcastEpisode) : array
    {
        $this->logger->debug('indexing '.$podcastEpisode->getTitle());

        return [
            'json' => $this->serialize($podcastEpisode),
            'type' => 'podcast-episode',
            'id' => $podcastEpisode->getNumber(),
        ];
    }

    /**
     * @GearmanTask(name="podcast_episode_insert", parameters={"json", "type", "id"})
     */
    public function insert(string $json, string $type, string  $id)
    {
        $this->logger->debug('inserting '.$json);
        $this->client->indexJsonDocument($type, $id, $json);

        return self::WORKFLOW_SUCCESS;
    }


    public function getSdkClass() : string
    {
        return PodcastEpisode::class;
    }
}
