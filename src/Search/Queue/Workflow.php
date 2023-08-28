<?php

namespace eLife\Search\Queue;

use eLife\Bus\Queue\QueueItem;
use eLife\Search\Api\ApiValidator;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;

use Symfony\Component\Serializer\Serializer;

use Psr\Log\LoggerInterface;

class Workflow
{
    private $workflows = [];

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

    public function create(QueueItem $item)
    {
        $type = $item->getType();

        switch ($type) {
            case 'article':
                return new ResearchArticleWorkflow($this->serializer, $this->logger, $this->client, $this->validator, $this->rdsArticles);
        }
    }

    /**
     * @param WorkflowInterface $workflow
     * @param $model
     */
    public function process(WorkflowInterface $workflow, $model)
    {
        var_dump("COMING FROM PROCESS");
        $res = $workflow->index($model);
        $workflow->insert($res['json'], $res['id']);
        $workflow->postValidate($res['id']);
    }
}