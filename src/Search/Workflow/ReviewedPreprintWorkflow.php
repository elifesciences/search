<?php

namespace eLife\Search\Workflow;

use Assert\Assertion;
use eLife\ApiSdk\Model\Model;
use eLife\ApiSdk\Model\ReviewedPreprint;
use eLife\Bus\Queue\InternalSqsMessage;
use eLife\Bus\Queue\WatchableQueue;
use eLife\Search\Api\ApiValidator;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use eLife\Search\Api\Elasticsearch\Response\DocumentResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;
use Throwable;

final class ReviewedPreprintWorkflow extends AbstractWorkflow
{
    use Blocks;
    use JsonSerializeTransport;
    use SortDate;

    const WORKFLOW_SUCCESS = 1;
    const WORKFLOW_FAILURE = -1;

    const VOR_TYPES = [
        'research-article',
        'tools-resources',
        'short-report',
        'research-advance',
        'correction',
        'editorial',
        'feature',
        'insight',
        'retraction',
        'review-article',
        'scientific-correspondence',
    ];
    /**
     * @var Serializer
     */
    private $serializer;
    private $client;
    private $validator;
    private $queue;

    public function __construct(
        Serializer $serializer,
        LoggerInterface $logger,
        MappedElasticsearchClient $client,
        ApiValidator $validator,
        WatchableQueue $queue
    )
    {
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->client = $client;
        $this->validator = $validator;
        $this->queue = $queue;
    }

    /**
     * @param ReviewedPreprint $reviewedPreprint
     */
    public function index(Model $reviewedPreprint) : array
    {
        // Don't index if article with same id present in index.
        foreach ([
            'research-article',
            'tools-resources',
            'short-report',
            'research-advance',
        ] as $type) {
            if ($this->client->getDocumentById($type.'-'. $reviewedPreprint->getId(), null, true) !== null) {
                return ['json' => '', 'id' => $reviewedPreprint->getId(), 'skipInsert' => true];
            }
        }

        $this->logger->debug('ReviewedPreprint<'.$reviewedPreprint->getId().'> Indexing '.$reviewedPreprint->getTitle());

        $reviewedPreprintObject = json_decode($this->serialize($reviewedPreprint));
        $reviewedPreprintObject->type = 'reviewed-preprint';
        $reviewedPreprintObject->body = $reviewedPreprint->getIndexContent() ?? '';

        $reviewedPreprintObject->snippet = ['format' => 'json', 'value' => json_encode($this->snippet($reviewedPreprint))];

        $this->addSortDate($reviewedPreprintObject, $reviewedPreprint->getStatusDate());

        $this->logger->debug('ReviewedPreprint<'.$reviewedPreprint->getId());

        return [
            'json' => json_encode($reviewedPreprintObject),
            'id' => $reviewedPreprintObject->type.'-'.$reviewedPreprint->getId(),
            'skipInsert' => false,
        ];
    }

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

    public function postValidate(string $id, bool $skipValidate) : int
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
            $this->queue->enqueue(new InternalSqsMessage('reviewed-preprint', $id));

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
