<?php

namespace eLife\Search\Workflow;

use eLife\ApiSdk\Model\BlogArticle;
use eLife\Search\Annotation\GearmanTask;
use eLife\Search\Api\Elasticsearch\ElasticsearchClient;
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
    private $client;
    private $cache;

    public function __construct(Serializer $serializer, LoggerInterface $logger, ElasticsearchClient $client)
    {
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->client = $client;
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

        return [
            'json' => $this->serializeArticle($blogArticle),
            'type' => 'blog-article',
            'id' => $blogArticle->getId(),
        ];
    }

    /**
     * @GearmanTask(name="blog_article_insert", parameters={"json", "type", "id"})
     */
    public function insert(string $json, string $type, string $id)
    {
        $this->logger->debug('inserting '.$json);
        $this->logger->debug('with type: `'.$type.'` and id: `'.$id.'`');
        $this->client->indexJsonDocument($type, $id, $json);
        $this->logger->debug('==========================================================================');

        return self::WORKFLOW_SUCCESS;
    }

    public function deserializeArticle(string $json) : BlogArticle
    {
        $key = sha1($json);
        if (!isset($this->cache[$key])) {
            $this->cache[$key] = $this->serializer->deserialize($json, BlogArticle::class, 'json');
        }

        return $this->cache[$key];
    }

    public function serializeArticle(BlogArticle $article) : string
    {
        $key = spl_object_hash($article);
        if (!isset($this->cache[$key])) {
            $this->cache[$key] = $this->serializer->serialize($article, 'json');
        }

        return $this->cache[$key];
    }
}
