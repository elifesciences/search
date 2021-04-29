<?php

namespace eLife\Search\Workflow;

use Assert\Assertion;
use eLife\ApiSdk\Model\LabsPost;
use eLife\Search\Annotation\GearmanTask;
use eLife\Search\Api\ApiValidator;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use eLife\Search\Api\Elasticsearch\Response\DocumentResponse;
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
        $labsPostObject->type = 'labs-post';
        $labsPostObject->body = $this->flattenBlocks($labsPostObject->content ?? []);
        unset($labsPostObject->content);
        $labsPostObject->snippet = ['format' => 'json', 'value' => json_encode($this->snippet($labsPost))];
        $this->addSortDate($labsPostObject, $labsPost->getPublishedDate());

        return [
            'json' => json_encode($labsPostObject),
            'id' => $labsPostObject->type.'-'.$labsPost->getId(),
        ];
    }

    /**
     * @GearmanTask(name="labs_post_insert", next="labs_post_post_validate", parameters={"json", "id"})
     */
    public function insert(string $json, string $id)
    {
        // Insert the document.
        $this->logger->debug('LabsPost<'.$id.'> importing into Elasticsearch.');
        $this->client->indexJsonDocument($id, $json);

        return [
            'id' => $id,
        ];
    }

    /**
     * @GearmanTask(name="labs_post_post_validate", parameters={"id"})
     */
    public function postValidate(string $id)
    {
        try {
            // Post-validation, we got a document.
            $document = $this->client->getDocumentById($id);
            Assertion::isInstanceOf($document, DocumentResponse::class);
            $result = $document->unwrap();
            // That labs post is valid JSON.
            $this->validator->validateSearchResult($result, true);
        } catch (Throwable $e) {
            $this->logger->error('LabsPost<'.$id.'> rolling back', [
                'exception' => $e,
            ]);
            $this->client->deleteDocument($id);

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
