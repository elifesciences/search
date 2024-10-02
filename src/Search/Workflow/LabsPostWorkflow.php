<?php

namespace eLife\Search\Workflow;

use eLife\ApiSdk\Model\LabsPost;
use eLife\ApiSdk\Model\Model;
use eLife\Search\Api\ApiValidator;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;

final class LabsPostWorkflow extends AbstractWorkflow
{
    use Blocks;
    use JsonSerializeTransport;
    use SortDate;

    /**
     * @var Serializer
     */
    private $serializer;

    public function __construct(Serializer $serializer, LoggerInterface $logger, MappedElasticsearchClient $client, ApiValidator $validator)
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

    public function getSdkClass() : string
    {
        return LabsPost::class;
    }
}
