<?php

namespace eLife\Search\Gearman\Command;

use eLife\Bus\Command\QueueCommand;
use eLife\Bus\Queue\QueueItem;
use eLife\Bus\Queue\QueueItemTransformer;
use eLife\Bus\Queue\WatchableQueue;
use eLife\Logging\Monitoring;
use eLife\Search\GearmanTransformer;
use GearmanClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

class QueueWatchCommand extends QueueCommand
{
    private $client;
    private $isMock;
    private $gearmanTransformer;

    public function __construct(
        WatchableQueue $queue,
        QueueItemTransformer $transformer,
        GearmanClient $client,
        bool $isMock,
        string $topic,
        LoggerInterface $logger,
        Monitoring $monitoring,
        callable $limit
    ) {
        parent::__construct($logger, $queue, $transformer, $monitoring, $limit);
        $this->client = $client;
        $this->isMock = $isMock;
        $this->gearmanTransformer = new GearmanTransformer();
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
        $gearmanTask = $this->gearmanTransformer->transform($item);

        if ($entity && $gearmanTask) {
            // Run the task.
            $this->logger->info($this->getName().' Running gearman task', [
                'gearmanTask' => $gearmanTask,
                'type' => $item->getType(),
                'id' => $item->getId(),
            ]);
            // Set the task to go.
            $this->client->doLowBackground($gearmanTask, $entity, md5($item->getReceipt()));
            // Commit.
            $this->queue->commit($item);
            $this->logger->info($this->getName().' Committed task', [
                'gearmanTask' => $gearmanTask,
                'type' => $item->getType(),
                'id' => $item->getId(),
            ]);
        }
    }
}
