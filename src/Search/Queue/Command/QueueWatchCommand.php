<?php

namespace eLife\Search\Queue\Command;

use eLife\Bus\Command\QueueCommand;
use eLife\Bus\Queue\QueueItem;
use eLife\Bus\Queue\QueueItemTransformer;
use eLife\Bus\Queue\WatchableQueue;
use eLife\Logging\Monitoring;
use eLife\Search\Queue\Workflow;
use eLife\Search\Queue\WorkflowInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

class QueueWatchCommand extends QueueCommand
{
    private $isMock;
    private $workflow;

    public function __construct(
        WatchableQueue $queue,
        QueueItemTransformer $transformer,
        Workflow $workflow,
        bool $isMock,
        string $topic,
        LoggerInterface $logger,
        Monitoring $monitoring,
        callable $limit
    ) {
        parent::__construct($logger, $queue, $transformer, $monitoring, $limit);
        $this->isMock = $isMock;
        $this->workflow = $workflow;
    }

    protected function configure()
    {
        $this
            ->setName('queue:watch')
            ->setDescription('Create queue watcher')
            ->setHelp('Creates process that will watch for incoming items on a queue')
            ->addArgument('id', InputArgument::OPTIONAL, 'Identifier to distinguish workers from each other');
    }

    protected function process(InputInterface $input, QueueItem $item, $entity = null)
    {
            $model = $this->transformer->transform($item, false);
            var_dump(get_class($model));
            $workflow = $this->workflow->create($item);
            $this->workflow->process($workflow, $model);
            die;
//            $workflow = $this->workflow->create($item);
//            // get the $item's workflow
//             $this->workflow->process($workflow);
    }
}
