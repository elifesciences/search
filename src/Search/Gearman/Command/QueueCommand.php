<?php

namespace eLife\Search\Gearman\Command;

use eLife\Search\Queue\Mock\QueueItemMock;
use eLife\Search\Queue\QueueItem;
use eLife\Search\Queue\QueueItemTransformer;
use eLife\Search\Queue\WatchableQueue;
use eLife\Search\Workflow\CliLogger;
use GearmanClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class QueueCommand extends Command
{
    private $queue;
    private $transformer;
    private $client;
    private $items_status;
    private $isMock;

    public function __construct(
        WatchableQueue $queue,
        QueueItemTransformer $transformer,
        GearmanClient $client,
        bool $isMock = false
    ) {
        $this->queue = $queue;
        $this->transformer = $transformer;
        $this->client = $client;
        $this->isMock = $isMock;
        parent::__construct(null);
    }

    protected function configure()
    {
        $this
            ->setName('queue:watch')
            ->setDescription('Create queue watcher')
            ->setHelp('Creates process that will watch for incoming items on a queue')
            ->addOption('interval', 'i', InputOption::VALUE_OPTIONAL, 'Time in seconds to reset between queue checking.', 10)
            ->addOption('timeout', 't', InputOption::VALUE_OPTIONAL, 'Timeout for process.', 3600)
            ->addOption('iterations', 'l', InputOption::VALUE_OPTIONAL, 'Max iterations before stopping.', 360)
            ->addOption('memory', 'm', InputOption::VALUE_OPTIONAL, 'Memory limit before exiting safely (Megabytes).', 360)
            ->addOption('memory-interval', 'M', InputOption::VALUE_OPTIONAL, 'How often to check memory.', 10)
            ->addOption('queue-timeout', 'T', InputOption::VALUE_OPTIONAL, 'Visibility Timeout for AWS queue item', 10)
            ->addOption('queue-interval', 'I', InputOption::VALUE_OPTIONAL, 'How many iterations before checking status of items.', 1)
            ->addArgument('topic', InputArgument::REQUIRED, 'Which topic to subscribe to.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Options.
        $logger = new CliLogger($input, $output);
        if ($this->isMock) {
            $logger->warning('This is using mocked information.');
        }
        $restTime = (int) $input->getOption('interval');
        $restTime = $restTime < 1 ? 10 : $restTime;
        $timeout = (int) $input->getOption('timeout');
        $maxIterations = $input->getOption('iterations');
        $memoryCheckInterval = $input->getOption('memory-interval');
        $queueCheckInterval = $input->getOption('queue-interval');
        $memoryThreshold = ($input->getOption('memory')) * 1000 * 1000;
        // Initial values.
        $startTime = time();
        $iterations = 0;
        // Loop.
        while (true) {
            ++$iterations;
            if ($iterations % $memoryCheckInterval === 0) {
                $memory = memory_get_usage();
                $logger->debug('Memory usage at '.memory_get_usage());
                if ($memory > $memoryThreshold) {
                    $logger->error('Memory limit reached, stopping script.');
                    break;
                }
            }
            if ($iterations === $maxIterations) {
                $logger->warning('Max iterations reached, stopping script.');
                break;
            }
            if (time() - $startTime >= $timeout) {
                $logger->warning('Max time reached, stopping script.');
                break;
            }
            if ($iterations % $queueCheckInterval === 0) {
                $this->checkStatus($input, $logger);
            }
            $this->loop($input, $logger);
            sleep($restTime);
        }
    }

    public function trackStatus(QueueItem $item)
    {
        $this->items_status[] = $item;
    }

    public function checkStatus(InputInterface $input, LoggerInterface $logger)
    {
        // @todo WARNING PSEUDO CODE.
        // If they are done, we will run: $this->queue->commit($item);
        // This will remove them from that internal queue.
        // @todo check if we need the store item_status with the other queue in place.
        // Check if we have items to check.
        if (!empty($this->items_status)) {
            // This will be where we ask gearman if our tasks are done.
            foreach ($this->items_status as $k => $item) {
                if ($item instanceof QueueItem) {
                    $status = $this->client->jobStatus($item->getReceipt().'--complete'); // Flag for last step.
                    // Something like this but nicer.
                    if (!isset($status[0])) {
                        $this->queue->commit($item);
                        unset($this->items_status[$k]);
                    }
                }
            }
        }
    }

    public function loop(InputInterface $input, LoggerInterface $logger)
    {
        $logger->info('Loop start... [');
        $topic = $input->getArgument('topic');
        $timeout = $input->getOption('queue-timeout');
        $logger->info('-> Hello queue. topic: '.$topic);
        if ($this->queue->isValid()) {
            $item = $this->queue->dequeue($timeout);
            // Set up some tracking.
            // @todo I think this will be handled by the dequeue method.
            // $this->trackStatus($item);
            // Transform into something for gearman.
            $entity = $this->transformer->transform($item);
            // Grab the gearman task.
            $gearmanTask = $this->transformer->getGearmanTask($item);
            // Run the task.
            $logger->info('-> Running task "'.$gearmanTask.'" for '.$item->getType().'<'.$item->getId().'>');
            // Set the task to go.
            $this->client->doLow($gearmanTask, $entity, md5($item->getReceipt()));
            // Commit.
            $this->queue->commit($item);
            $logger->info('-> Committed task "'.$gearmanTask.'" for '.$item->getType().'<'.$item->getId().'>');
        }
        $logger->info('-> Adding new blog article');
        $this->queue->enqueue(new QueueItemMock('blog-article', 359325));
        $logger->info("] Loop end\n");
    }
}
