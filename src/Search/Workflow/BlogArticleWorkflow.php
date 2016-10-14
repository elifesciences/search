<?php

namespace eLife\Search\Workflow;

use Assert\Assertion;
use DateTime;
use eLife\ApiSdk\Model\BlogArticle;
use eLife\Search\Annotation\GearmanTask;
use eLife\Search\Api\ApiValidator;
use eLife\Search\Api\Elasticsearch\ElasticsearchClient;
use eLife\Search\Api\Elasticsearch\Response\DocumentResponse;
use eLife\Search\Api\Response\BlogArticleResponse;
use eLife\Search\Gearman\InvalidWorkflowException;
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
    private $jms_serializer;
    private $validator;

    public function __construct(Serializer $serializer, LoggerInterface $logger, ElasticsearchClient $client, ApiValidator $validator)
    {
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->client = $client;
        $this->validator = $validator;
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
        // Create response to validate.
        $searchBlogArticle = new BlogArticleResponse();
        $searchBlogArticle->id = $blogArticle->getId();
        $searchBlogArticle->title = $blogArticle->getTitle();
        $searchBlogArticle->impactStatement = $blogArticle->getImpactStatement();
        /*  @SuppressWarnings(ForbiddenDateTime) */
        $searchBlogArticle->published = DateTime::createFromFormat(DATE_RFC2822, $blogArticle->getPublishedDate()->format(DATE_RFC2822));
        // Validate that response.
        $isValid = $this->validator->validateSearchResult($searchBlogArticle);
        if ($isValid === false) {
            $this->logger->alert($this->validator->getLastError()->getMessage());
            throw new InvalidWorkflowException('BlogArticle<'.$blogArticle->getId().'> Invalid item tried to be imported.');
        }
        // Log results.
        $this->logger->info('BlogArticle<'.$blogArticle->getId().'> validated against current schema.');

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
        // This step is still not used very much.
        $this->logger->debug('BlogArticle<'.$blogArticle->getId().'> Indexing '.$blogArticle->getTitle());
        // Return.
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
        // Insert the document.
        $this->logger->debug('BlogArticle<'.$id.'> importing into Elasticsearch.');
        $this->client->indexJsonDocument($type, $id, $json);
        // Post-validation, we got a document.
        $document = $this->client->getDocumentById($type, $id);
        Assertion::isInstanceOf($document, DocumentResponse::class);
        $result = $document->unwrap();
        // That document contains a blog article.
        Assertion::isInstanceOf($result, BlogArticleResponse::class);
        // That blog article is valid JSON.
        $isValid = $this->validator->validateSearchResult($result);
        if ($isValid === false) {
            $this->logger->alert($this->validator->getLastError()->getMessage());
            $this->client->deleteDocument($type, $id);
            throw new InvalidWorkflowException('BlogArticle<'.$id.'> invalid after inserting into Elasticsearch, rolling back.');
        } else {
            $this->logger->info('BlogArticle<'.$id.'> successfully imported.');
            $this->logger->debug('==========================================================================');
        }

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
