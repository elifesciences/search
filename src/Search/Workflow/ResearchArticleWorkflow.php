<?php

namespace eLife\Search\Workflow;

use Assert\Assertion;
use eLife\ApiSdk\Model\ArticleVersion;
use eLife\Search\Annotation\GearmanTask;
use eLife\Search\Api\ApiValidator;
use eLife\Search\Api\Elasticsearch\ElasticsearchClient;
use eLife\Search\Api\Elasticsearch\Response\DocumentResponse;
use eLife\Search\Api\Response\ArticleResponse;
use eLife\Search\Api\Response\SearchResult;
use eLife\Search\Gearman\InvalidWorkflow;
use Psr\Log\LoggerInterface;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Serializer\Serializer;
use Throwable;

final class ResearchArticleWorkflow implements Workflow
{
    const WORKFLOW_SUCCESS = 1;
    const WORKFLOW_FAILURE = -1;

    use JsonSerializeTransport;
    use SortDate;

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
     *     serialize="serialize",
     *     priority="medium"
     * )
     */
    public function validate(ArticleVersion $article) : ArticleVersion
    {
        $this->logger->debug('ResearchArticle<'.$article->getId().'> Validating '.$article->getTitle());
        $articleSearchResponse = $this->validator->deserialize($this->serialize($article), SearchResult::class);
        // @todo remove hack at some point.
        if ($articleSearchResponse->image) {
            $articleSearchResponse->image = $articleSearchResponse->image->https();
        }
        // Validate that response.
        $isValid = $this->validator->validateSearchResult($articleSearchResponse);
        if ($isValid === false) {
            $this->logger->error(
                'ResearchArticle<'.$article->getId().'> cannot be transformed into a valid search result',
                [
                    'input' => [
                        'type' => 'article',
                        'id' => $article->getId(),
                    ],
                    'search_result' => $this->validator->serialize($articleSearchResponse),
                    'validation_error' => $this->validator->getLastError()->getMessage(),
                ]
            );
            throw new InvalidWorkflow('ResearchArticle<'.$article->getId().'> cannot be trasformed into a valid search result.');
        }
        $this->logger->info('ResearchArticle<'.$article->getId().'> validated against current schema.');

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
        $this->logger->debug('ResearchArticle<'.$article->getId().'> Indexing '.$article->getTitle());

        $articleObject = json_decode($this->serialize($article));
        // Fix author name.
        $articleObject->authors = array_map(function ($author) {
            if (is_string($author->name)) {
                $author->name = ['value' => $author->name];
            }

            return $author;
        }, $articleObject->authors);
        // Fix author name in references.
        $articleObject->references = array_map(function ($reference) {
            $reference->authors = array_map(function ($author) {
                if (is_string($author->name)) {
                    $author->name = ['value' => $author->name];
                }

                return $author;
            }, $reference->authors ?? []);

            return $reference;
        }, $articleObject->authors);
        // Flatten body complexity.
        $articleObject->body_keywords = iterator_to_array(new RecursiveIteratorIterator(new RecursiveArrayIterator(
            array_map(function ($bodyItem) {
                return [$bodyItem->title ?? null, array_map(function ($content) {
                    return array_filter([
                        $content->id ?? null,
                        $content->label ?? null,
                        $content->alt ?? null,
                        $content->text ?? null,
                        $content->caption->text ?? null,
                    ]);
                }, $bodyItem->content ?? [])];
            }, $articleObject->body ?? [])
        )), false);
        // But maintain original content.
        $articleObject->body = [
            'format' => 'json',
            'value' => json_encode($articleObject->body ?? '[]'),
        ];
        // Flatten authorResponse complexity.
        $articleObject->authorResponse_keywords = iterator_to_array(new RecursiveIteratorIterator(new RecursiveArrayIterator(
            array_map(function ($authorResponseItem) {
                return [$authorResponseItem->title ?? null, array_map(function ($content) {
                    return array_filter([
                        $content->id ?? null,
                        $content->label ?? null,
                        $content->alt ?? null,
                        $content->text ?? null,
                        $content->caption->text ?? null,
                    ]);
                }, $authorResponseItem->content ?? [])];
            }, $articleObject->authorResponse ?? [])
        )), false);
        // But maintain original content.
        $articleObject->authorResponse = [
            'format' => 'json',
            'value' => json_encode($articleObject->authorResponse ?? '[]'),
        ];
        // Completely serialize funding
        $articleObject->funding = [
            'format' => 'json',
            'value' => json_encode($articleObject->funding ?? '[]'),
        ];
        // Completely serialize dataSets
        $articleObject->dataSets = [
            'format' => 'json',
            'value' => json_encode($articleObject->dataSets ?? '[]'),
        ];

        $sortDate = $article->getStatusDate() ? $article->getStatusDate() : $article->getPublishedDate();
        $this->addSortDate($articleObject, $sortDate);

        $this->logger->debug('Article<'.$article->getId().'> Detected type '.($article->getType() ?? 'research-article'));

        return [
            'json' => json_encode($articleObject),
            'type' => $article->getType() ?? 'research-article',
            'id' => $article->getId(),
        ];
    }

    /**
     * @GearmanTask(name="research_article_insert", next="research_article_post_validate", parameters={"json", "type", "id"})
     */
    public function insert(string $json, string $type, string $id)
    {
        // Insert the document.
        $this->logger->debug('ResearchArticle<'.$id.'> importing into Elasticsearch.');
        $this->client->indexJsonDocument($type, $id, $json);

        return [
            'type' => $type,
            'id' => $id,
        ];
    }

    /**
     * @GearmanTask(name="research_article_post_validate", parameters={"type", "id"})
     */
    public function postValidate(string $type, string $id)
    {
        $this->logger->debug('ResearchArticle<'.$id.'> post validation.');
        try {
            // Post-validation, we got a document.
            $document = $this->client->getDocumentById($type, $id);
            Assertion::isInstanceOf($document, DocumentResponse::class);
            $result = $document->unwrap();
            // That document contains a blog article.
            Assertion::isInstanceOf($result, ArticleResponse::class);
            // That blog article is valid JSON.
            $this->validator->validateSearchResult($result, true);
        } catch (Throwable $e) {
            $this->logger->error('ResearchArticle<'.$id.'> rolling back', [
                'exception' => $e,
            ]);
            $this->client->deleteDocument($type, $id);
            // We failed.
            return self::WORKFLOW_FAILURE;
        }
        $this->logger->info('ResearchArticle<'.$id.'> successfully imported.');

        return self::WORKFLOW_SUCCESS;
    }

    public function getSdkClass() : string
    {
        return ArticleVersion::class;
    }
}
