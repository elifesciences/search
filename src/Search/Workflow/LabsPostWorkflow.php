<?php

namespace eLife\Search\Workflow;

use Assert\Assertion;
use eLife\ApiSdk\Model\LabsPost;
use eLife\Search\Annotation\GearmanTask;
use eLife\Search\Api\ApiValidator;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use eLife\Search\Api\Elasticsearch\Response\DocumentResponse;
use eLife\Search\Api\Response\LabsPostResponse;
use eLife\Search\Gearman\InvalidWorkflow;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;
use Throwable;

final class LabsPostWorkflow implements Workflow
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

    public function __construct(Serializer $serializer, LoggerInterface $logger, MappedElasticsearchClient $client, ApiValidator $validator)
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
        if (false === $isValid) {
            $this->logger->error(
                'LabsPost<'.$labsPost->getId().'> cannot be transformed into a valid search result',
                [
                    'input' => [
                        'type' => 'labs-post',
                        'id' => $labsPost->getId(),
                    ],
                    'search_result' => $this->validator->serialize($searchLabsPost),
                    'validation_error' => $this->validator->getLastError()->getMessage(),
                ]
            );
            throw new InvalidWorkflow('LabsPost<'.$labsPost->getId().'> cannot be trasformed into a valid search result.');
        }
        // Log results.
        $this->logger->info('LabsPost<'.$labsPost->getId().'> validated against current schema.');

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
        $this->logger->debug('LabsPost<'.$labsPost->getId().'> Indexing '.$labsPost->getTitle());

        // Normalized fields.
        $labsPostObject = json_decode($this->serialize($labsPost));
        $labsPostObject->body = $this->flattenBlocks($labsPostObject->content ?? []);
        unset($labsPostObject->content);
        $this->addSortDate($labsPostObject, $labsPost->getPublishedDate());

        return [
            'json' => json_encode($labsPostObject),
            'type' => 'labs-post',
            'id' => $labsPost->getId(),
        ];
    }

    /**
     * @GearmanTask(name="labs_post_insert", next="labs_post_post_validate", parameters={"json", "type", "id"})
     */
    public function insert(string $json, string $type, string $id)
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
