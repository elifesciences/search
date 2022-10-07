<?php

namespace eLife\Search\Workflow;

use Assert\Assertion;
use DateTimeImmutable;
use eLife\ApiSdk\Model\ReviewedPreprint;
use eLife\Search\Annotation\GearmanTask;
use eLife\Search\Api\ApiValidator;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use eLife\Search\Api\Elasticsearch\Response\DocumentResponse;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Serializer\Serializer;
use Throwable;

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
    private $reviewedPreprints;

    public function __construct(
        Serializer $serializer,
        LoggerInterface $logger,
        MappedElasticsearchClient $client,
        ApiValidator $validator,
        array $reviewedPreprints = []
    ) {
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->client = $client;
        $this->validator = $validator;
        $this->reviewedPreprints = $reviewedPreprints;
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
        $this->logger->debug('ReviewedPreprint<'.$reviewedPreprint->getId().'> Indexing '.$reviewedPreprint->getTitle());

        $reviewedPreprintObject = json_decode($this->serialize($reviewedPreprint));

        // Fix author name.
        $reviewedPreprintObject->authors = array_map(function ($author) {
            if (is_string($author->name)) {
                $author->name = ['value' => $author->name];
            }

            return $author;
        }, $reviewedPreprintObject->authors ?? []);
        // Fix author name in references.
        $reviewedPreprintObject->references = array_map(function ($reference) {
            $reference->authors = array_map(function ($author) {
                if (is_string($author->name)) {
                    $author->name = ['value' => $author->name];
                }

                return $author;
            }, $reference->authors ?? []);

            return $reference;
        }, $reviewedPreprintObject->authors);
        $reviewedPreprintObject->abstract = $this->flattenBlocks($reviewedPreprintObject->abstract->content ?? []);
        $reviewedPreprintObject->digest = $this->flattenBlocks($reviewedPreprintObject->digest->content ?? []);
        $reviewedPreprintObject->body = $this->flattenBlocks($reviewedPreprintObject->body ?? []);
        if (!empty($reviewedPreprintObject->appendices)) {
            $appendices = '';
            foreach ($reviewedPreprintObject->appendices ?? [] as $appendix) {
                $appendices .= $this->flattenBlocks($appendix->content ?? []);
            }
            $reviewedPreprintObject->appendices = $appendices;
        }
        $reviewedPreprintObject->acknowledgements = $this->flattenBlocks($reviewedPreprintObject->acknowledgements ?? []);
        $reviewedPreprintObject->decisionLetter = $this->flattenBlocks($reviewedPreprintObject->decisionLetter->content ?? []);
        $reviewedPreprintObject->authorResponse = $this->flattenBlocks($reviewedPreprintObject->authorResponse->content ?? []);
        // Completely serialize funding
        $reviewedPreprintObject->funding = [
            'format' => 'json',
            'value' => json_encode($reviewedPreprintObject->funding ?? '[]'),
        ];
        // Completely serialize dataSets
        $reviewedPreprintObject->dataSets = [
            'format' => 'json',
            'value' => json_encode($reviewedPreprintObject->dataSets ?? '[]'),
        ];
        $reviewedPreprintObject->snippet = ['format' => 'json', 'value' => json_encode($this->snippet($reviewedPreprint))];

        if (isset($this->reviewedPreprints[$reviewedPreprint->getId()]['date'])) {
            $sortDate = DateTimeImmutable::createFromFormat(DATE_ATOM, $this->reviewedPreprints[$reviewedPreprint->getId()]['date']);
            if (false === $sortDate) {
                throw new RuntimeException($this->reviewedPreprints[$reviewedPreprint->getId()]['date'].' is not a valid date');
            }
        } else {
            $sortDate = $reviewedPreprint->getStatusDate();
        }
        $this->addSortDate($reviewedPreprintObject, $sortDate);

        $this->logger->debug('ReviewedPreprint<'.$reviewedPreprint->getId());

        return [
            'json' => json_encode($reviewedPreprintObject),
            'id' => $reviewedPreprint->getId(),
        ];
    }

    /**
     * @GearmanTask(name="reviewed_preprint_insert", next="reviewed_preprint_post_validate", parameters={"json", "id"})
     */
    public function insert(string $json, string $id)
    {
        // Insert the document.
        $this->logger->debug('ReviewedPreprint<'.$id.'> importing into Elasticsearch.');
        $this->client->indexJsonDocument($id, $json);

        return [
            'id' => $id,
        ];
    }

    /**
     * @GearmanTask(name="reviewed_preprint_post_validate", parameters={"id"})
     */
    public function postValidate(string $id)
    {
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
