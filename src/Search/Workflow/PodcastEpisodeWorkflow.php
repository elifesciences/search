<?php

namespace eLife\Search\Workflow;

use Assert\Assertion;
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
     * @param PodcastEpisode $podcastEpisode
     * @return array
     */
    public function index($podcastEpisode) : array
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

    public function insert(string $json, string $id)
    {
        // Insert the document.
        $this->logger->debug('PodcastEpisode<'.$id.'> importing into Elasticsearch.');
        $this->client->indexJsonDocument($id, $json);

        return [
            'id' => $id,
        ];
    }

    public function postValidate($id)
    {
        try {
            // Post-validation, we got a document.
            $document = $this->client->getDocumentById($id);
            Assertion::isInstanceOf($document, DocumentResponse::class);
            $result = $document->unwrap();
            // That podcast episode is valid JSON.
            $this->validator->validateSearchResult($result, true);
        } catch (Throwable $e) {
            $this->logger->error('PodcastEpisode<'.$id.'> rolling back', [
                'exception' => $e,
                'document' => $result ?? null,
            ]);
            $this->client->deleteDocument($id);

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
