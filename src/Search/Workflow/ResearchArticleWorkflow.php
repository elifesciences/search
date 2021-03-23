<?php

namespace eLife\Search\Workflow;

use Assert\Assertion;
use DateTimeImmutable;
use eLife\ApiSdk\Model\ArticleVersion;
use eLife\Search\Annotation\GearmanTask;
use eLife\Search\Api\ApiValidator;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use eLife\Search\Api\Elasticsearch\Response\DocumentResponse;
use eLife\Search\Api\Response\ArticleResponse;
use eLife\Search\Api\Response\SearchResult;
use eLife\Search\Gearman\InvalidWorkflow;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Serializer\Serializer;
use Throwable;

final class ResearchArticleWorkflow implements Workflow
{
    use Blocks;
    use JsonSerializeTransport;
    use SortDate;

    const WORKFLOW_SUCCESS = 1;
    const WORKFLOW_FAILURE = -1;

    /**
     * @var Serializer
     */
    private $serializer;
    private $logger;
    private $client;
    private $validator;
    private $rdsArticles;

    public function __construct(
        Serializer $serializer,
        LoggerInterface $logger,
        MappedElasticsearchClient $client,
        ApiValidator $validator,
        array $rdsArticles = []
    ) {
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->client = $client;
        $this->validator = $validator;
        $this->rdsArticles = $rdsArticles;
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
        if (false === $isValid) {
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
        }, $articleObject->authors ?? []);
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
        $articleObject->body = $this->flattenBlocks($articleObject->body ?? []);
        foreach ($articleObject->appendices ?? [] as $appendix) {
            $appendix->content = $this->flattenBlocks($appendix->content ?? []);
        }
        $articleObject->acknowledgements = $this->flattenBlocks($articleObject->acknowledgements ?? []);
        $articleObject->decisionLetter = $this->flattenBlocks($articleObject->decisionLetter->content ?? []);
        $articleObject->authorResponse = $this->flattenBlocks($articleObject->authorResponse->content ?? []);
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
        $articleObject->snippet = $this->serializer->normalize(
            $article,
            null,
            ['snippet' => true, 'type' => true]
        );

        if (isset($this->rdsArticles[$article->getId()]['date'])) {
            $sortDate = DateTimeImmutable::createFromFormat(DATE_ATOM, $this->rdsArticles[$article->getId()]['date']);
            if (false === $sortDate) {
                throw new RuntimeException($this->rdsArticles[$article->getId()]['date'].' is not a valid date');
            }
        } else {
            $sortDate = $article->getStatusDate();
        }
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
