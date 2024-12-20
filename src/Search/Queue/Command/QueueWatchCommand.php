<?php

namespace eLife\Search\Queue\Command;

use eLife\Bus\Command\QueueCommand;
use eLife\Bus\Queue\QueueItem;
use eLife\Bus\Queue\QueueItemTransformer;
use eLife\Bus\Queue\WatchableQueue;
use eLife\Logging\Monitoring;
use eLife\Search\Indexer\Indexer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;

class QueueWatchCommand extends QueueCommand
{
    private $indexer;

    public function __construct(
        WatchableQueue $queue,
        QueueItemTransformer $transformer,
        Indexer $indexer,
        LoggerInterface $logger,
        Monitoring $monitoring,
        callable $limit
    ) {
        parent::__construct($logger, $queue, $transformer, $monitoring, $limit, false);
        $this->indexer = $indexer;
    }

    protected function configure()
    {
        $this
            ->setName('queue:watch')
            ->setDescription('Create queue watcher')
            ->setHelp('Creates process that will watch for incoming items on a queue');
    }

    protected function process(InputInterface $input, QueueItem $item, $entity = null)
    {
        $this->indexer->index($entity);
    }
}
