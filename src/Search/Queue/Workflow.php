<?php

namespace eLife\Search\Queue;

use eLife\Bus\Queue\QueueItem;
use eLife\Bus\Queue\QueueItemTransformer;
use eLife\Search\Api\ApiValidator;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;

use eLife\Search\Workflow\BlogArticleWorkflow;
use eLife\Search\Workflow\CollectionWorkflow;
use eLife\Search\Workflow\InterviewWorkflow;
use eLife\Search\Workflow\LabsPostWorkflow;
use eLife\Search\Workflow\PodcastEpisodeWorkflow;
use eLife\Search\Workflow\ReviewedPreprintWorkflow;
use eLife\Search\Workflow\WorkflowInterface;
use Symfony\Component\Serializer\Serializer;
use eLife\Search\Workflow\ResearchArticleWorkflow;
use Psr\Log\LoggerInterface;

class Workflow
{
    private $workflows = [];

    private $serializer;
    private $logger;
    private $client;
    private $validator;
    private $rdsArticles;
    private $transformer;

    public function __construct(
        Serializer $serializer,
        LoggerInterface $logger,
        MappedElasticsearchClient $client,
        ApiValidator $validator,
        QueueItemTransformer $transformer,
        array $rdsArticles = []
    ) {
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->client = $client;
        $this->validator = $validator;
        $this->rdsArticles = $rdsArticles;
        $this->transformer = $transformer;
    }

    public function getWorkflow(QueueItem $item): WorkflowInterface
    {
        $type = $item->getType();

        switch ($type) {
            case 'article':
                return new ResearchArticleWorkflow($this->serializer, $this->logger, $this->client, $this->validator, $this->rdsArticles);
            case 'blog-article':
                return new BlogArticleWorkflow($this->serializer, $this->logger, $this->client, $this->validator);
            case 'interview':
                return new InterviewWorkflow($this->serializer, $this->logger, $this->client, $this->validator);
            case 'reviewed-preprint':
                return new ReviewedPreprintWorkflow($this->serializer, $this->logger, $this->client, $this->validator);
            case 'labs-post':
                return new LabsPostWorkflow($this->serializer, $this->logger, $this->client, $this->validator);
            case 'podcast-episode':
                return new PodcastEpisodeWorkflow($this->serializer, $this->logger, $this->client, $this->validator);
            case 'collection':
                return new CollectionWorkflow($this->serializer, $this->logger, $this->client, $this->validator);
        }

        throw new \InvalidArgumentException("The {$item->getType()} is not valid.");
    }

    public function process(QueueItem $item)
    {
        // convert $item to sdk class e.g. ArticleVersion
        $entity = $this->transformer->transform($item, false);

        // get corresponding workflow
        $workflow = $this->getWorkflow($item);
        $workflow->run($entity);
    }
}
