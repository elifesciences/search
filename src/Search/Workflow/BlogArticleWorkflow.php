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

    /**
     * @GearmanTask(
     *     name="blog_article_validate",
     *     next="blog_article_insert",
     *     deserialize="deserializeArticle",
     *     serialize="serializeArticle"
     * )
     */
    public function validate(BlogArticle $blogArticle) : BlogArticle
    {
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
        $index = [];

        return [$this->serializeArticle($blogArticle), $index];
    }

    /**
     * @GearmanTask(name="blog_article_insert")
     */
    public function insert(string $json, array $index)
    {
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
