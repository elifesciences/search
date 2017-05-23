<?php

namespace eLife\Search\Workflow;

use Assert\Assertion;
use eLife\ApiSdk\Model\LabsPost;
use eLife\Search\Annotation\GearmanTask;
use eLife\Search\Api\ApiValidator;
use eLife\Search\Api\Elasticsearch\ElasticsearchClient;
use eLife\Search\Api\Elasticsearch\Response\DocumentResponse;
use eLife\Search\Api\Response\LabsPostResponse;
use eLife\Search\Gearman\InvalidWorkflow;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;
use Throwable;

final class LabsPostWorkflow implements Workflow
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

    public function __construct(Serializer $serializer, LoggerInterface $logger, ElasticsearchClient $client, ApiValidator $validator)
    {
        $this->serializer = $serializer;
        $this->client = $client;
        $this->logger = $logger;
        $this->validator = $validator;
    }

    /**
     * @GearmanTask(
     *     name="labs_post_validate",
     *     next="labs_post_index",
     *     deserialize="deserialize",
     *     serialize="serialize"
     * )
     */
    public function validate(LabsPost $labsPost) : LabsPost
    {
        // Create response to validate.
        $searchLabsPost = $this->validator->deserialize($this->serialize($labsPost), LabsPostResponse::class);
        // Validate that response.
        $isValid = $this->validator->validateSearchResult($searchLabsPost);
        if ($isValid === false) {
            $this->logger->error(
                'LabsPost<'.$labsPost->getNumber().'> cannot be transformed into a valid search result',
                [
                    'input' => [
                        'type' => 'labs-post',
                        'number' => $labsPost->getNumber(),
                    ],
                    'search_result' => $this->validator->serialize($searchLabsPost),
                    'validation_error' => $this->validator->getLastError()->getMessage(),
                ]
            );
            throw new InvalidWorkflow('LabsPost<'.$labsPost->getNumber().'> cannot be trasformed into a valid search result.');
        }
        // Log results.
        $this->logger->info('LabsPost<'.$labsPost->getNumber().'> validated against current schema.');
        // Pass it on.
        return $labsPost;
    }

    /**
     * @GearmanTask(
     *     name="labs_post_index",
     *     next="labs_post_insert",
     *     deserialize="deserialize"
     * )
     */
    public function index(LabsPost $labsPost) : array
    {
        $this->logger->debug('LabsPost<'.$labsPost->getNumber().'> Indexing '.$labsPost->getTitle());

        // Normalized fields.
        $labspostObject = json_decode($this->serialize($labsPost));
        $this->addSortDate($labspostObject, $labsPost->getPublishedDate());

        return [
            'json' => json_encode($labspostObject),
            'type' => 'labs-post',
            'id' => $labsPost->getNumber(),
        ];
    }

    /**
     * @GearmanTask(name="labs_post_insert", next="labs_post_post_validate", parameters={"json", "type", "id"})
     */
    public function insert(string $json, string $type, string  $id)
    {
        // Insert the document.
        $this->logger->debug('LabsPost<'.$id.'> importing into Elasticsearch.');
        $this->client->indexJsonDocument($type, $id, $json);

        return [
            'type' => $type,
            'id' => $id,
        ];
    }

    /**
     * @GearmanTask(name="labs_post_post_validate", parameters={"type", "id"})
     */
    public function postValidate(string $type, string $id)
    {
        try {
            // Post-validation, we got a document.
            $document = $this->client->getDocumentById($type, $id);
            Assertion::isInstanceOf($document, DocumentResponse::class);
            $result = $document->unwrap();
            // That document contains a blog article.
            Assertion::isInstanceOf($result, LabsPostResponse::class);
            // That blog article is valid JSON.
            $this->validator->validateSearchResult($result, true);
        } catch (Throwable $e) {
            $this->logger->error('LabsPost<'.$id.'> rolling back', [
                'exception' => $e,
            ]);
            $this->client->deleteDocument($type, $id);
            // We failed.
            return self::WORKFLOW_FAILURE;
        }

        $this->logger->info('LabsPost<'.$id.'> successfully imported.');

        return self::WORKFLOW_SUCCESS;
    }

    public function getSdkClass() : string
    {
        return LabsPost::class;
    }
}
