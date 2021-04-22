<?php

namespace eLife\Search\Workflow;

use Assert\Assertion;
use DateTimeImmutable;
use eLife\ApiSdk\Model\ArticleVersion;
use eLife\Search\Annotation\GearmanTask;
use eLife\Search\Api\ApiValidator;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use eLife\Search\Api\Elasticsearch\Response\DocumentResponse;
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
        $articleObject->snippet = ['format' => 'json', 'value' => json_encode($this->snippet($article))];

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
            'id' => ($article->getType() ?? 'research-article').'-'.$article->getId(),
        ];
    }

    /**
     * @GearmanTask(name="research_article_insert", next="research_article_post_validate", parameters={"json", "id"})
     */
    public function insert(string $json, string $id)
    {
        // Insert the document.
        $this->logger->debug('ResearchArticle<'.$id.'> importing into Elasticsearch.');
        $this->client->indexJsonDocument($id, $json);

        return [
            'id' => $id,
        ];
    }

    /**
     * @GearmanTask(name="research_article_post_validate", parameters={"id"})
     */
    public function postValidate(string $id)
    {
        $this->logger->debug('ResearchArticle<'.$id.'> post validation.');
        try {
            // Post-validation, we got a document.
            $document = $this->client->getDocumentById($id);
            Assertion::isInstanceOf($document, DocumentResponse::class);
            $result = $document->unwrap();
            // That research article is valid JSON.
            $this->validator->validateSearchResult($result, true);
        } catch (Throwable $e) {
            $this->logger->error('ResearchArticle<'.$id.'> rolling back', [
                'exception' => $e,
            ]);
            $this->client->deleteDocument($id);

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
