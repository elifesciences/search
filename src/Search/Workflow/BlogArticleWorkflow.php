<?php

namespace eLife\Search\Workflow;

use eLife\ApiSdk\Model\BlogArticle;
use eLife\Search\Annotation\GearmanTask;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;

final class BlogArticleWorkflow implements Workflow
{
    const WORKFLOW_SUCCESS = 1;
    const WORKFLOW_FAILURE = -1;

    /**
     * @var Serializer
     */
    private $serializer;
    private $logger;

    public function __construct(Serializer $serializer, LoggerInterface $logger)
    {
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    /**
     * @GearmanTask(
     *     name="blog_article_validate",
     *     next="blog_article_index",
     *     deserialize="deserializeArticle",
     *     serialize="serializeArticle"
     * )
     */
    public function validate(BlogArticle $blogArticle) : BlogArticle
    {
        $this->logger->debug('validating '.$blogArticle->getTitle());

        return $blogArticle;
    }

    /**
     * @GearmanTask(
     *     name="blog_article_index",
     *     next="blog_article_insert",
     *     deserialize="deserializeArticle"
     * )
     */
    public function index(BlogArticle $blogArticle) : array
    {
        $this->logger->debug('indexing '.$blogArticle->getTitle());
        $index = ['testing' => 'cheese'];

        return ['json' => $this->serializeArticle($blogArticle), 'index' => $index];
    }

    /**
     * @GearmanTask(name="blog_article_insert", parameters={"json", "index"})
     */
    public function insert(string $json, array $index)
    {
        $this->logger->debug('inserting '.$json);
        $this->logger->debug('==========================================================================');

        return self::WORKFLOW_SUCCESS;
    }

    public function deserializeArticle(string $json) : BlogArticle
    {
        return $this->serializer->deserialize($json, BlogArticle::class, 'json');
    }

    public function serializeArticle(BlogArticle $blogArticle) : string
    {
        return $this->serializer->serialize($blogArticle, 'json');
    }
}
