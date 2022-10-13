<?php

namespace eLife\Search\Workflow;

use Assert\Assertion;
use DateTimeImmutable;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use eLife\ApiSdk\Model\ReviewedPreprint;
use eLife\Search\Annotation\GearmanTask;
use eLife\Search\Api\ApiValidator;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use eLife\Search\Api\Elasticsearch\Response\DocumentResponse;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Serializer\Serializer;
use Throwable;
use function GuzzleHttp\Psr7\try_fopen;

final class ReviewedPreprintWorkflow implements Workflow
{
    use Blocks;
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

    public function __construct(
        Serializer $serializer,
        LoggerInterface $logger,
        MappedElasticsearchClient $client,
        ApiValidator $validator
    ) {
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->client = $client;
        $this->validator = $validator;
    }

    /**
     * @GearmanTask(
     *     name="reviewed_preprint_index",
     *     next="reviewed_preprint_insert",
     *     deserialize="deserialize"
     * )
     */
    public function index(ReviewedPreprint $reviewedPreprint) : array
    {
        // Don't index if article with same id present in index.
        try {
            $this->client->getDocumentById('research-article-'. $reviewedPreprint->getId(), ['ignore' => [404]);
            return ['json' => '', 'id' => $reviewedPreprint->getId(), 'skipInsert' => true];
        } catch (Missing404Exception $exception) {
            // we are free to index
        }

        $this->logger->debug('ReviewedPreprint<'.$reviewedPreprint->getId().'> Indexing '.$reviewedPreprint->getTitle());

        $reviewedPreprintObject = json_decode($this->serialize($reviewedPreprint));
        $reviewedPreprintObject->type = 'reviewed-preprint';
        $reviewedPreprintObject->body = $reviewedPreprint->getIndexContent() ?? '';

        $reviewedPreprintObject->snippet = ['format' => 'json', 'value' => json_encode($this->snippet($reviewedPreprint))];

        $this->addSortDate($reviewedPreprintObject, $reviewedPreprint->getReviewedDate());

        $this->logger->debug('ReviewedPreprint<'.$reviewedPreprint->getId());

        return [
            'json' => json_encode($reviewedPreprintObject),
            'id' => $reviewedPreprintObject->type.'-'.$reviewedPreprint->getId(),
            'skipInsert' => false,
        ];
    }

    /**
     * @GearmanTask(name="reviewed_preprint_insert", next="reviewed_preprint_post_validate", parameters={"json", "id", "skipInsert"})
     */
    public function insert(string $json, string $id, bool $skipInsert)
    {
        if ($skipInsert) {
            $this->logger->debug('ReviewedPreprint<'.$id.'> no need to index.');
            return ['id' => $id, 'skipValidate' => true];
        }
        // Insert the document.
        $this->logger->debug('ReviewedPreprint<'.$id.'> importing into Elasticsearch.');
        $this->client->indexJsonDocument($id, $json);

        return [
            'id' => $id,
            'skipValidate' => false,
        ];
    }

    /**
     * @GearmanTask(name="reviewed_preprint_post_validate", parameters={"id", "skipValidate"})
     */
    public function postValidate(string $id, bool $skipValidate)
    {
        if ($skipValidate) {
            $this->logger->debug('ReviewedPreprint<'.$id.'> no need to validate.');
            return self::WORKFLOW_SUCCESS;
        }

        $this->logger->debug('ReviewedPreprint<'.$id.'> post validation.');
        try {
            // Post-validation, we got a document.
            $document = $this->client->getDocumentById($id);
            Assertion::isInstanceOf($document, DocumentResponse::class);
            $result = $document->unwrap();
            // That research reviewed preprint is valid JSON.
            $this->validator->validateSearchResult($result, true);
        } catch (Throwable $e) {
            $this->logger->error('ReviewedPreprint<'.$id.'> rolling back', [
                'exception' => $e,
            ]);
            $this->client->deleteDocument($id);

            // We failed.
            return self::WORKFLOW_FAILURE;
        }
        $this->logger->info('ReviewedPreprint<'.$id.'> successfully imported.');

        return self::WORKFLOW_SUCCESS;
    }

    public function getSdkClass() : string
    {
        return ReviewedPreprint::class;
    }
}
