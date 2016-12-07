<?php

namespace eLife\Search\Workflow;

use Assert\Assertion;
use eLife\ApiSdk\Model\BlogArticle;
use eLife\Search\Annotation\GearmanTask;
use eLife\Search\Api\ApiValidator;
use eLife\Search\Api\Elasticsearch\ElasticsearchClient;
use eLife\Search\Api\Elasticsearch\Response\DocumentResponse;
use eLife\Search\Api\Response\BlogArticleResponse;
use eLife\Search\Gearman\InvalidWorkflow;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;
use Throwable;

final class BlogArticleWorkflow implements Workflow
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
     *     deserialize="deserialize",
     *     serialize="serialize"
     * )
     * @SuppressWarnings(ForbiddenDateTime)
     */
    public function validate(BlogArticle $blogArticle) : BlogArticle
    {
        // Create response to validate.
        $searchBlogArticle = $this->validator->deserialize($this->serialize($blogArticle), BlogArticleResponse::class);
        // Validate that response.
        $isValid = $this->validator->validateSearchResult($searchBlogArticle);
        if ($isValid === false) {
            $this->logger->alert($this->validator->getLastError()->getMessage());
            throw new InvalidWorkflow('BlogArticle<'.$blogArticle->getId().'> Invalid item tried to be imported.');
        }
        // Log results.
        $this->logger->info('BlogArticle<'.$blogArticle->getId().'> validated against current schema.');
        // Pass it on.
        return $blogArticle;
    }

    /**
     * @GearmanTask(
     *     name="blog_article_index",
     *     next="blog_article_insert",
     *     deserialize="deserialize"
     * )
     */
    public function index(BlogArticle $blogArticle) : array
    {
        // This step is still not used very much.
        $this->logger->debug('BlogArticle<'.$blogArticle->getId().'> Indexing '.$blogArticle->getTitle());
        // Return.
        return [
            'json' => $this->serialize($blogArticle),
            'type' => 'blog-article',
            'id' => $blogArticle->getId(),
        ];
    }

    /**
     * @GearmanTask(name="blog_article_insert", next="blog_article_post_validate", parameters={"json", "type", "id"})
     */
    public function insert(string $json, string $type, string $id) : array
    {
        // Insert the document.
        $this->logger->debug('BlogArticle<'.$id.'> importing into Elasticsearch.');
        $this->client->indexJsonDocument($type, $id, $json);

        return [
            'type' => $type,
            'id' => $id,
        ];
    }

    /**
     * @GearmanTask(name="blog_article_post_validate", parameters={"type", "id"})
     */
    public function postValidate(string $type, string $id) : int
    {
        try {
            // Post-validation, we got a document.
            $document = $this->client->getDocumentById($type, $id);
            Assertion::isInstanceOf($document, DocumentResponse::class);
            $result = $document->unwrap();
            // That document contains a blog article.
            Assertion::isInstanceOf($result, BlogArticleResponse::class);
            // That blog article is valid JSON.
            $this->validator->validateSearchResult($result, true);
        } catch (Throwable $e) {
            $this->logger->alert('BlogArticle<'.$id.'> rolling back', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
            $this->client->deleteDocument($type, $id);
            // We failed.
            return self::WORKFLOW_FAILURE;
        }

        $this->logger->info('BlogArticle<'.$id.'> successfully imported.');

        return self::WORKFLOW_SUCCESS;
    }

    public function getSdkClass() : string
    {
        return BlogArticle::class;
    }
}
