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
        // This step is still not used very much.
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
            'value' => json_encode($articleObject->body),
        ];

        return [
            'json' => json_encode($articleObject),
            'type' => 'research-article',
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
            $this->logger->alert($e->getMessage());
            $this->logger->alert('ResearchArticle<'.$id.'> rolling back');
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
