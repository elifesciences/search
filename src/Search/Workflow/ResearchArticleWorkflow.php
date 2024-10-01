<?php

namespace eLife\Search\Workflow;

use Assert\Assertion;
use DateTimeImmutable;
use eLife\ApiSdk\Model\ArticleVersion;
use eLife\ApiSdk\Model\ArticleVoR;
use eLife\ApiSdk\Model\Model;
use eLife\Search\Api\ApiValidator;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use eLife\Search\Api\Elasticsearch\Response\DocumentResponse;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Serializer\Serializer;
use Throwable;

final class ResearchArticleWorkflow extends AbstractWorkflow
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
     * @param ArticleVersion $article
     * @return array
     */
    public function index(Model $article) : array
    {
        $this->logger->debug('ResearchArticle<'.$article->getId().'> Indexing '.$article->getTitle());

        $articleObject = json_decode($this->serialize($article));
        // Fix author name.
        $articleObject->authors = array_map(function ($author) {
            // @todo - investigate whether it is right to assume that name property exists.
            if (is_string($author->name ?? null)) {
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
        $articleObject->abstract = $this->flattenBlocks($articleObject->abstract->content ?? []);
        $articleObject->digest = $this->flattenBlocks($articleObject->digest->content ?? []);
        $articleObject->body = $this->flattenBlocks($articleObject->body ?? []);
        if ($article instanceof ArticleVoR) {
            $articleObject->body .= $this->flattenBlocks($articleObject->publicReviews ?? []);
            $articleObject->body .= $this->flattenBlocks($articleObject->elifeAssessment->content ?? []);
            $articleObject->body .= $this->flattenBlocks($articleObject->recommendationsForAuthors->content ?? []);
            unset($articleObject->publicReviews);
            unset($articleObject->elifeAssessment);
            unset($articleObject->recommendationsForAuthors);
        }
        if (!empty($articleObject->appendices)) {
            $appendices = '';
            foreach ($articleObject->appendices ?? [] as $appendix) {
                $appendices .= $this->flattenBlocks($appendix->content ?? []);
            }
            $articleObject->appendices = $appendices;
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

        $snippet = $this->snippet($article);

        if ($article instanceof ArticleVoR) {
            $this->logger->debug('Article<'.$article->getId().'> delete corresponding reviewed preprint from index, if exists');
            $this->client->deleteDocument('reviewed-preprint-'.$article->getId());
        }

        $articleObject->snippet = ['format' => 'json', 'value' => json_encode($snippet)];

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

    public function getSdkClass() : string
    {
        return ArticleVersion::class;
    }
}
