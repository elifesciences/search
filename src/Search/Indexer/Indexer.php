<?php

namespace eLife\Search\Indexer;

use eLife\ApiSdk\Model\HasIdentifier;
use eLife\ApiSdk\Model\Model;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use eLife\Search\Api\HasSearchResultValidator;
use eLife\Search\Api\Elasticsearch\Response\IsDocumentResponse;
use Psr\Log\LoggerInterface;
use InvalidArgumentException;
use Throwable;
use Assert\Assertion;
use eLife\Search\Indexer\ModelIndexer\ResearchArticleIndexer;
use eLife\Search\Indexer\ModelIndexer\BlogArticleIndexer;
use eLife\Search\Indexer\ModelIndexer\InterviewIndexer;
use eLife\Search\Indexer\ModelIndexer\ReviewedPreprintIndexer;
use eLife\Search\Indexer\ModelIndexer\LabsPostIndexer;
use eLife\Search\Indexer\ModelIndexer\PodcastEpisodeIndexer;
use eLife\Search\Indexer\ModelIndexer\CollectionIndexer;
use Symfony\Component\Serializer\Serializer;

class Indexer
{
    private $logger;
    private $client;
    private $validator;
    private $modelIndexer;

    public function __construct(
        LoggerInterface $logger,
        MappedElasticsearchClient $client,
        HasSearchResultValidator $validator,
        array $modelIndexer = []
    ) {
        $this->logger = $logger;
        $this->client = $client;
        $this->validator = $validator;
        $this->modelIndexer = $modelIndexer;
    }

    public static function getDefaultModelIndexers(Serializer $serializer, MappedElasticsearchClient $client, $rdsArticles) : array
    {
        return [
            'article' => new ResearchArticleIndexer($serializer, $rdsArticles),
            'blog-article' => new BlogArticleIndexer($serializer),
            'interview' => new InterviewIndexer($serializer),
            'reviewed-preprint' => new ReviewedPreprintIndexer($serializer, $client),
            'labs-post' => new LabsPostIndexer($serializer),
            'podcast-episode' => new PodcastEpisodeIndexer($serializer),
            'collection' => new CollectionIndexer($serializer),

        ];
    }

    public function getModelIndexer($type): ModelIndexer
    {
        if (!isset($this->modelIndexer[$type])) {
            throw new InvalidArgumentException("The {$type} is not valid.");
        }

        return $this->modelIndexer[$type];
    }

    public function index($entity): ChangeSet
    {
        if (!$entity instanceof Model || !$entity instanceof HasIdentifier) {
            throw new InvalidArgumentException('The given Entity is not an '.Model::class.' or '.HasIdentifier::class);
        }
        $modelIndexer = $this->getModelIndexer($entity->getIdentifier()->getType());

        $debugId = '<'.$entity->getIdentifier().'>';

        $this->logger->debug($debugId.' preparing for indexing.');
        $changeSet = $modelIndexer->prepareChangeSet($entity);

        $inserts = $changeSet->getInserts();
        if (count($inserts) === 0) {
            $this->logger->debug($debugId.' skipping indexing');
        }

        foreach ($inserts as $insert) {
            $doc = $insert['json'];
            $docId = $insert['id'];
            $this->logger->debug($debugId.' importing into Elasticsearch.');
            $this->insert($doc, $docId);

            $this->logger->debug($debugId.' post validating.');
            try {
                $this->postValidate($docId);
            } catch (Throwable $e) {
                $this->logger->error($debugId.' rolling back.', [
                    'exception' => $e,
                    'document' => $result ?? null,
                ]);
                $this->client->deleteDocument($docId);

                // We failed.
                throw new \Exception($debugId.' post validate failed.');
            }

            $this->logger->info($debugId.' successfully imported.');
        }

        foreach ($changeSet->getDeletes() as $deleteId) {
            $this->logger->debug('<'.$deleteId.'> removing from index.');
            $this->client->deleteDocument($deleteId);
        }

        return $changeSet;
    }

    public function insert(string $json, string $id)
    {
        // Insert the document.
        $this->client->indexJsonDocument($id, $json);
        return [
            'id' => $id,
        ];
    }

    public function postValidate($id)
    {
        // Post-validation, we got a document.
        $document = $this->client->getDocumentById($id);
        Assertion::isInstanceOf($document, IsDocumentResponse::class);
        $result = $document->unwrap();
        // That the document is valid JSON.
        $this->validator->validateSearchResult($result, true);
    }
}
