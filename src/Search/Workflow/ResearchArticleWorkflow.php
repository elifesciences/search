<?php

namespace eLife\Search\Workflow;

use eLife\ApiSdk\Model\ArticleVersion;
use eLife\Search\Annotation\GearmanTask;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;

final class ResearchArticleWorkflow implements Workflow
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
     *     name="research_article_validate",
     *     next="research_article_index",
     *     deserialize="deserializeArticle",
     *     serialize="serializeArticle"
     * )
     */
    public function validate(ArticleVersion $article) : ArticleVersion
    {
        $this->logger->debug('validating '.$article->getTitle());

        return $article;
    }

    /**
     * @GearmanTask(
     *     name="research_article_index",
     *     next="research_article_insert",
     *     deserialize="deserializeArticle"
     * )
     */
    public function index(ArticleVersion $article) : array
    {
        $this->logger->debug('indexing '.$article->getTitle());
        $index = ['testing' => 'cheese'];

        return ['json' => $this->serializeArticle($article), 'index' => $index];
    }

    /**
     * @GearmanTask(name="research_article_insert", parameters={"json", "index"})
     */
    public function insert(string $json, array $index)
    {
        $this->logger->debug('inserting '.$json);
        $this->logger->debug('with index '.json_encode($index));
        $this->logger->debug('==========================================================================');

        return self::WORKFLOW_SUCCESS;
    }

    public function deserializeArticle(string $json) : ArticleVersion
    {
        // This probably won't work.
        return $this->serializer->deserialize($json, ArticleVersion::class, 'json');
    }

    public function serializeArticle(ArticleVersion $article) : string
    {
        return $this->serializer->serialize($article, 'json');
    }
}
