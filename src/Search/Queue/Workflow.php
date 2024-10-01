<?php

namespace eLife\Search\Queue;

use eLife\Bus\Queue\QueueItem;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use eLife\Search\Api\HasSearchResultValidator;
use eLife\Search\Workflow\AbstractWorkflow;
use eLife\Search\Workflow\BlogArticleWorkflow;
use eLife\Search\Workflow\CollectionWorkflow;
use eLife\Search\Workflow\InterviewWorkflow;
use eLife\Search\Workflow\LabsPostWorkflow;
use eLife\Search\Workflow\PodcastEpisodeWorkflow;
use eLife\Search\Workflow\ReviewedPreprintWorkflow;
use Symfony\Component\Serializer\Serializer;
use eLife\Search\Workflow\ResearchArticleWorkflow;
use Psr\Log\LoggerInterface;

class Workflow
{
    private $serializer;
    private $logger;
    private $client;
    private $validator;
    private $rdsArticles;

    private $workflowClasses = [
        'article' => ResearchArticleWorkflow::class,
        'blog-article' => BlogArticleWorkflow::class,
        'interview' => InterviewWorkflow::class,
        'reviewed-preprint' => ReviewedPreprintWorkflow::class,
        'labs-post' => LabsPostWorkflow::class,
        'podcast-episode' => PodcastEpisodeWorkflow::class,
        'collection' => CollectionWorkflow::class,
    ];

    public function __construct(
        Serializer $serializer,
        LoggerInterface $logger,
        MappedElasticsearchClient $client,
        HasSearchResultValidator $validator,
        array $rdsArticles = []
    ) {
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->client = $client;
        $this->validator = $validator;
        $this->rdsArticles = $rdsArticles;
    }

    public function getWorkflow(QueueItem $item): AbstractWorkflow
    {
        $type = $item->getType();

        if (isset($this->workflowClasses[$type])) {
            return new $this->workflowClasses[$type](
                $this->serializer,
                $this->logger,
                $this->client,
                $this->validator,
                ...$this->getExtraArguments($type)
            );
        }

        throw new \InvalidArgumentException("The {$type} is not valid.");
    }

    public function process(QueueItem $item, $entity)
    {
        $workflow = $this->getWorkflow($item);
        $workflow->run($entity);
    }

    private function getExtraArguments(string $type): array
    {
        if ($type === 'article') {
            return [$this->rdsArticles];
        }

        return [];
    }
}
