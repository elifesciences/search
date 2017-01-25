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
            $this->logger->error(
                'BlogArticle<'.$blogArticle->getId().'> cannot be transformed into a valid search result',
                [
                    'input' => [
                        'type' => 'blog-article',
                        'id' => $blogArticle->getId(),
                    ],
                    'search_result' => $this->validator->serialize($searchBlogArticle),
                    'validation_error' => $this->validator->getLastError()->getMessage(),
                ]
            );
            throw new InvalidWorkflow('BlogArticle<'.$blogArticle->getId().'> cannot be transformed into a valid search result.');
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
        $this->logger->debug('BlogArticle<'.$blogArticle->getId().'> Indexing '.$blogArticle->getTitle());
        // Normalized fields.
        $blogArticleObject = json_decode($this->serialize($blogArticle));
        $sortDate = $blogArticle->getPublishedDate();
        $blogArticleObject->sortDate = $sortDate->format('Y-m-d\TH:i:s\Z');

        // Return.
        return [
            'json' => json_encode($blogArticleObject),
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
            $this->logger->error('BlogArticle<'.$id.'> rolling back', [
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
