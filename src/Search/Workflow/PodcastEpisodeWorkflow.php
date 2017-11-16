<?php

namespace eLife\Search\Workflow;

use Assert\Assertion;
use eLife\ApiSdk\Model\PodcastEpisode;
use eLife\Search\Annotation\GearmanTask;
use eLife\Search\Api\ApiValidator;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use eLife\Search\Api\Elasticsearch\Response\DocumentResponse;
use eLife\Search\Api\Response\PodcastEpisodeResponse;
use eLife\Search\Gearman\InvalidWorkflow;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;
use Throwable;

final class PodcastEpisodeWorkflow implements Workflow
{
    const WORKFLOW_SUCCESS = 1;
    const WORKFLOW_FAILURE = -1;

    use JsonSerializeTransport;
    use SortDate;

    /**
     * @var Serializer
     */
    private $serializer;
    private $logger;
    private $client;
    private $validator;

    public function __construct(Serializer $serializer, LoggerInterface $logger, MappedElasticsearchClient $client, ApiValidator $validator)
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
        // Create response to validate.
        $searchPodcastEpisode = $this->validator->deserialize($this->serialize($podcastEpisode), PodcastEpisodeResponse::class);
        // Validate response.
        $isValid = $this->validator->validateSearchResult($searchPodcastEpisode);
        if ($isValid === false) {
            $this->logger->error(
                'PodcastEpisode<'.$podcastEpisode->getNumber().'> cannot be transformed into a valid search result',
                [
                    'input' => [
                        'type' => 'podcast-episode',
                        'number' => $podcastEpisode->getNumber(),
                    ],
                    'search_result' => $this->validator->serialize($searchPodcastEpisode),
                    'validation_error' => $this->validator->getLastError()->getMessage(),
                ]
            );
            throw new InvalidWorkflow('PodcastEpisode<'.$podcastEpisode->getNumber().'> cannot be trasformed into a valid search result.');
        }
        // Log results.
        $this->logger->info('PodcastEpisode<'.$podcastEpisode->getNumber().'> validated against current schema.');

        // Pass it on.
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

        // Normalized fields.
        $podcastEpisodeObject = json_decode($this->serialize($podcastEpisode));
        // Add sort date.
        $this->addSortDate($podcastEpisodeObject, $podcastEpisode->getPublishedDate());

        return [
            'json' => json_encode($podcastEpisodeObject),
            'type' => 'podcast-episode',
            'id' => $podcastEpisode->getNumber(),
        ];
    }

    /**
     * @GearmanTask(
     *     name="podcast_episode_insert",
     *     parameters={"json", "type", "id"},
     *     next="podcast_episode_post_validate"
     * )
     */
    public function insert(string $json, string $type, string $id)
    {
        // Insert the document.
        $this->logger->debug('PodcastEpisode<'.$id.'> importing into Elasticsearch.');
        $this->client->indexJsonDocument($type, $id, $json);

        return [
            'type' => $type,
            'id' => $id,
        ];
    }

    /**
     * @GearmanTask(
     *     name="podcast_episode_post_validate",
     *     parameters={"type", "id"}
     * )
     */
    public function postValidate($type, $id)
    {
        try {
            // Post-validation, we got a document.
            $document = $this->client->getDocumentById($type, $id);
            Assertion::isInstanceOf($document, DocumentResponse::class);
            $result = $document->unwrap();
            // That document contains a blog article.
            Assertion::isInstanceOf($result, PodcastEpisodeResponse::class);
            // That blog article is valid JSON.
            $this->validator->validateSearchResult($result, true);
        } catch (Throwable $e) {
            $this->logger->error('PodcastEpisode<'.$id.'> rolling back', [
                'exception' => $e,
                'document' => $result ?? null,
            ]);
            $this->client->deleteDocument($type, $id);

            // We failed.
            return self::WORKFLOW_FAILURE;
        }

        $this->logger->info('PodcastEpisode<'.$id.'> successfully imported.');

        return self::WORKFLOW_SUCCESS;
    }

    public function getSdkClass() : string
    {
        return PodcastEpisode::class;
    }
}
