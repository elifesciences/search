<?php

namespace eLife\Search\Workflow;

use Assert\Assertion;
use eLife\ApiSdk\Model\BlogArticle;
use eLife\ApiSdk\Model\Model;
use eLife\Search\Api\ApiValidator;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use eLife\Search\Api\Elasticsearch\Response\DocumentResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;
use Throwable;

final class BlogArticleWorkflow extends AbstractWorkflow
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

    public function __construct(Serializer $serializer, LoggerInterface $logger, MappedElasticsearchClient $client, ApiValidator $validator)
    {
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->client = $client;
        $this->validator = $validator;
    }

    /**
     * @param BlogArticle $blogArticle
     * @return array
     */
    public function index(Model $blogArticle) : array
    {
        $this->logger->debug('BlogArticle<'.$blogArticle->getId().'> Indexing '.$blogArticle->getTitle());
        // Normalized fields.
        $blogArticleObject = json_decode($this->serialize($blogArticle));
        $blogArticleObject->type = 'blog-article';
        $blogArticleObject->body = $this->flattenBlocks($blogArticleObject->content ?? []);
        unset($blogArticleObject->content);
        $blogArticleObject->snippet = ['format' => 'json', 'value' => json_encode($this->snippet($blogArticle))];
        $this->addSortDate($blogArticleObject, $blogArticle->getPublishedDate());

        // Return.
        return [
            'json' => json_encode($blogArticleObject),
            'id' => $blogArticleObject->type.'-'.$blogArticle->getId(),
        ];
    }

    public function getSdkClass() : string
    {
        return BlogArticle::class;
    }
}
