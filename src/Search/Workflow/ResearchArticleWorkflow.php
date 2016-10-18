<?php

namespace eLife\Search\Workflow;

use DateTime;
use eLife\ApiSdk\Model\ArticleVersion;
use eLife\ApiSdk\Model\ArticleVoR;
use eLife\Search\Annotation\GearmanTask;
use eLife\Search\Api\ApiValidator;
use eLife\Search\Api\Elasticsearch\ElasticsearchClient;
use eLife\Search\Api\Response\ArticleResponse\PoaArticle;
use eLife\Search\Api\Response\ArticleResponse\VorArticle;
use eLife\Search\Api\Response\ImageResponse;
use eLife\Search\Api\Response\SearchResult;
use eLife\Search\Gearman\InvalidWorkflow;
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
    private $client;
    private $cache;
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
     *     deserialize="deserializeArticle",
     *     serialize="serializeArticle"
     * )
     */
    public function validate(ArticleVersion $article) : ArticleVersion
    {
        //        if ($article instanceof ArticleVoR) {
//            $articleResponse = new VorArticle();
//        } else {
//            $articleResponse = new PoaArticle();
//        }
//        if (method_exists($article, 'getStatusDate')) {
//            $articleResponse->statusDate;
//        }
//        $articleResponse->id = $article->getId();
//        $articleResponse->title = $article->getTitle();
//        if (method_exists($article, 'getImpactStatement')) {
//            $articleResponse->impactStatement = $article->getImpactStatement();
//        }
//        $articleResponse->type = $article->getType();
//        if (method_exists($article, 'getImage')) {
//            $image = $article->getImage();
//            if ($image) {
//                $images = [];
//                foreach ($image->getSizes() as $imageSize) {
//                    foreach ($imageSize->getImages() as $k => $m) {
//                        $images[$k] = $m;
//                    }
//                }
//                $articleResponse->image = new ImageResponse($image->getAltText(), $images);
//            }
//        }
//        $articleResponse->volume = $article->getVolume();
//        $articleResponse->version = $article->getVersion();
//        $articleResponse->issue = $article->getIssue();
//        if (method_exists($article, 'getTitlePrefix')) {
//            $articleResponse->titlePrefix = $article->getTitlePrefix();
//        }
//        $articleResponse->elocationId = $article->getElocationId();
//        $articleResponse->doi = $article->getDoi();
//        $articleResponse->authorLine = $article->getAuthorLine();
//        $articleResponse->pdf = $article->getPdf();
//        $articleResponse->type = 'research-article';
//        $articleResponse->published = DateTime::createFromFormat(DATE_RFC2822, $article->getPublishedDate()->format(DATE_RFC2822));

        $articleSearchResponse = $this->validator->deserialize($this->serializeArticle($article), SearchResult::class);
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
     *     deserialize="deserializeArticle"
     * )
     */
    public function index(ArticleVersion $article) : array
    {
        $this->logger->debug('indexing '.$article->getTitle());

        return [
            'json' => $this->serializeArticle($article),
            'type' => 'research-article',
            'id' => $article->getId(),
        ];
    }

    /**
     * @GearmanTask(name="research_article_insert", parameters={"json", "type", "id"})
     */
    public function insert(string $json, string $type, string $id)
    {
        $this->logger->debug('inserting '.$json);
        $this->logger->debug('with type: `'.$type.'` and id: `'.$id.'`');
        $this->client->indexJsonDocument($type, $id, $json);
        $this->logger->debug('==========================================================================');

        return self::WORKFLOW_SUCCESS;
    }

    public function deserializeArticle(string $json) : ArticleVersion
    {
        $key = sha1($json);
        if (!isset($this->cache[$key])) {
            $this->cache[$key] = $this->serializer->deserialize($json, ArticleVersion::class, 'json');
        }

        return $this->cache[$key];
    }

    public function serializeArticle(ArticleVersion $article) : string
    {
        $key = spl_object_hash($article);
        if (!isset($this->cache[$key])) {
            $this->cache[$key] = $this->serializer->serialize($article, 'json');
        }

        return $this->cache[$key];
    }
}
