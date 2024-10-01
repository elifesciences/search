<?php

namespace eLife\Search\Workflow;

use Assert\Assertion;
use eLife\ApiSdk\Model\LabsPost;
use eLife\ApiSdk\Model\Model;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use eLife\Search\Api\Elasticsearch\Response\DocumentResponse;
use eLife\Search\Api\HasSearchResultValidator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;
use Throwable;

final class LabsPostWorkflow extends AbstractWorkflow
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
    private $client;
    private $validator;

    public function __construct(
        Serializer $serializer,
        LoggerInterface $logger,
        MappedElasticsearchClient $client,
        HasSearchResultValidator $validator
    )
    {
        $this->serializer = $serializer;
        $this->client = $client;
        $this->logger = $logger;
        $this->validator = $validator;
    }

    /**
     * @param LabsPost $labsPost
     * @return array
     */
    public function index(Model $labsPost) : array
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

    public function insert(string $json, string $id, bool $skipInsert = false)
    {
        // Insert the document.
        $this->logger->debug('LabsPost<'.$id.'> importing into Elasticsearch.');
        $this->client->indexJsonDocument($id, $json);

        return [
            'id' => $id,
        ];
    }

    public function postValidate(string $id, bool $skipValidate = false) : int
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
