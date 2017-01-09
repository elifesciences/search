<?php

namespace eLife\Search\Gearman\Command;

use eLife\ApiClient\Exception\BadResponse;
use eLife\Search\Monitoring;
use eLife\Search\Queue\InternalSqsMessage;
use eLife\Search\Queue\QueueItem;
use eLife\Search\Queue\QueueItemTransformer;
use eLife\Search\Queue\WatchableQueue;
use GearmanClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class QueueCommand extends Command
{
    private $queue;
    private $transformer;
    private $client;
    private $isMock;
    private $topic;
    private $logger;
    private $monitoring;
    private $limit;

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
        $this->queue = $queue;
        $this->transformer = $transformer;
        $this->client = $client;
        $this->isMock = $isMock;
        $this->topic = $topic;
        $this->logger = $logger;
        $this->monitoring = $monitoring;
        $this->limit = $limit;

        parent::__construct(null);
    }

    protected function configure()
    {
        $this
            ->setName('queue:watch')
            ->setDescription('Create queue watcher')
            ->setHelp('Creates process that will watch for incoming items on a queue')
            ->addOption('interval', 'i', InputOption::VALUE_OPTIONAL, 'Time in seconds to reset between queue checking.', 10)
            ->addOption('mock', 'k', InputOption::VALUE_OPTIONAL, 'How many mock items to start with', 0)
            ->addArgument('id', InputArgument::OPTIONAL, 'Identifier to distinguish workers from each other');
    }

    protected function mock(OutputInterface $output, int $mocks)
    {
        $progress = new ProgressBar($output, $mocks);
        for ($i = 0; $i < $mocks; ++$i) {
            $progress->advance();
            // These will work with real or mocked queues.
            $this->queue->enqueue(new InternalSqsMessage('blog-article', 359325));
        }
        $progress->finish();
        $this->logger->info("\nAdded ".$mocks.' blog articles');
        if ($this->isMock === false) {
            // Exit the application here if we have real data.
            exit;
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Options.
        if ($this->isMock) {
            $this->logger->warning('queue:watch: This is using mocked information.');
        }
        if ($mocks = $input->getOption('mock')) {
            $this->mock($output, $mocks);
        }
        $this->logger->info('queue:watch: Started listening.');
        $this->monitoring->nameTransaction('queue:watch');
        // Loop.
        $limit = $this->limit;
        while (!$limit()) {
            $this->loop($input);
        }
        $this->logger->info('queue:watch: Stopped because of limits reached.');
    }

    public function transform(QueueItem $item)
    {
        $entity = null;
        try {
            // Transform into something for gearman.
            $entity = $this->transformer->transform($item);
        } catch (BadResponse $e) {
            // We got a 404 or server error.
            $this->logger->error("queue:watch: Item does not exist in API: {$item->getType()} ({$item->getId()})", [
                'exception' => $e,
                'item' => $item,
            ]);
            // Remove from queue.
            $this->queue->commit($item);
        } catch (Throwable $e) {
            // Unknown error.
            $this->logger->error("queue:watch: There was an unknown problem importing {$item->getType()} ({$item->getId()})", [
                'exception' => $e,
                'item' => $item,
            ]);
            $this->monitoring->recordException($e, "Error in importing {$item->getType()} {$item->getId()}");
            // Remove from queue.
            $this->queue->commit($item);
        }

        return $entity;
    }

    public function loop(InputInterface $input)
    {
        $this->logger->debug('queue:watch: Loop start, listening to queue', ['queue' => $this->topic]);
        $item = $this->queue->dequeue();
        if ($item) {
            $this->monitoring->startTransaction();
            if ($entity = $this->transform($item)) {
                // Grab the gearman task.
                $gearmanTask = $this->transformer->getGearmanTask($item);
                // Run the task.
                $this->logger->info('queue:watch: Running gearman task', [
                    'gearmanTask' => $gearmanTask,
                    'type' => $item->getType(),
                    'id' => $item->getId(),
                ]);
                // Set the task to go.
                $this->client->doLowBackground($gearmanTask, $entity, md5($item->getReceipt()));
                // Commit.
                $this->queue->commit($item);
                $this->logger->info('queue:watch: Committed task', [
                    'gearmanTask' => $gearmanTask,
                    'type' => $item->getType(),
                    'id' => $item->getId(),
                ]);
            }
            $this->monitoring->endTransaction();
        }
        $this->logger->debug('queue:watch: End of loop');
    }
}
