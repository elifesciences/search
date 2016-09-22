<?php

namespace eLife\Search\Workflow;

use eLife\ApiSdk\Model\BlogArticle;
use eLife\Search\Annotation\GearmanTask;
use Symfony\Component\Serializer\Serializer;

final class BlogArticleWorkflow implements Workflow
{
    const WORKFLOW_SUCCESS = 1;
    const WORKFLOW_FAILURE = -1;

    /**
     * @var Serializer
     */
    private $serializer;

    public function __construct(Serializer $serializer)
    {
        $this->serializer = $serializer;
    }

    public function log(string ...$log)
    {
        echo 'WORKER: '.implode(' ', $log).PHP_EOL;
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
        $this->log('validating', $blogArticle->getTitle());

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
        $this->log('indexing', $blogArticle->getTitle());
        $index = ['testing' => 'cheese'];

        return ['json' => $this->serializeArticle($blogArticle), 'index' => $index];
    }

    /**
     * @GearmanTask(name="blog_article_insert", parameters={"json", "index"})
     */
    public function insert(string $json, array $index)
    {
        $this->log('inserting', $json);
        $this->log('==========================================================================');

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
