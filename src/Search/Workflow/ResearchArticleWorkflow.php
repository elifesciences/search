<?php

namespace eLife\Search\Workflow;

use eLife\ApiSdk\Model\ArticleVersion;
use eLife\Search\Annotation\GearmanTask;
use eLife\Search\Api\ApiValidator;
use eLife\Search\Api\Elasticsearch\ElasticsearchClient;
use eLife\Search\Api\Response\SearchResult;
use eLife\Search\Gearman\InvalidWorkflow;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;

final class ResearchArticleWorkflow implements Workflow
{
    const WORKFLOW_SUCCESS = 1;
    const WORKFLOW_FAILURE = -1;

    use JsonSerializeTransport;

    /**
     * @var Serializer
     */
    private $serializer;
    private $logger;
    private $client;
    private $validator;

    public function __construct(
        Serializer $serializer,
        LoggerInterface $logger,
        ElasticsearchClient $client,
        ApiValidator $validator
    ) {
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->client = $client;
        $this->validator = $validator;
    }

    /**
     * @GearmanTask(
     *     name="research_article_validate",
     *     next="research_article_index",
     *     deserialize="deserialize",
     *     serialize="serialize"
     * )
     */
    public function validate(ArticleVersion $article) : ArticleVersion
    {
        $articleSearchResponse = $this->validator->deserialize($this->serialize($article), SearchResult::class);
        // @todo remove hack at some point.
        if ($articleSearchResponse->image) {
            $articleSearchResponse->image = $articleSearchResponse->image->https();
        }
        // Validate that response.
        $isValid = $this->validator->validateSearchResult($articleSearchResponse);
        if ($isValid === false) {
            $this->logger->alert($this->validator->getLastError()->getMessage());
            throw new InvalidWorkflow('ResearchArticle<'.$article->getId().'> Invalid item tried to be imported.');
        }
        $this->logger->debug('validating '.$article->getTitle());

        return $article;
    }

    /**
     * @GearmanTask(
     *     name="research_article_index",
     *     next="research_article_insert",
     *     deserialize="deserialize"
     * )
     */
    public function index(ArticleVersion $article) : array
    {
        $this->logger->debug('indexing '.$article->getTitle());

        return [
            'json' => $this->serialize($article),
            'type' => 'research-article',
            'id' => $article->getId(),
        ];
    }

    /**
     * @GearmanTask(name="research_article_insert", parameters={"json", "type", "id"})
     */
    public function insert(string $json, string $type, string $id)
    {
        $this->logger->debug('with type: `'.$type.'` and id: `'.$id.'`');
        $this->client->indexJsonDocument($type, $id, $json);
        $this->logger->debug('==========================================================================');

        return self::WORKFLOW_SUCCESS;
    }

    public function getSdkClass() : string
    {
        return ArticleVersion::class;
    }
}
