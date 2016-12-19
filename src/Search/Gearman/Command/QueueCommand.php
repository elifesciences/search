<?php

namespace eLife\Search\Gearman\Command;

use eLife\ApiClient\Exception\BadResponse;
use eLife\Search\Queue\Mock\QueueItemMock;
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

    public function __construct(
        WatchableQueue $queue,
        QueueItemTransformer $transformer,
        GearmanClient $client,
        bool $isMock,
        string $topic,
        LoggerInterface $logger
    ) {
        $this->queue = $queue;
        $this->transformer = $transformer;
        $this->client = $client;
        $this->isMock = $isMock;
        $this->topic = $topic;
        $this->logger = $logger;
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
            ->addOption('mock', 'k', InputOption::VALUE_OPTIONAL, 'How many mock items to start with', 0)
            ->addArgument('id', InputArgument::OPTIONAL, 'Identifier to distinguish workers from each other');
    }

    protected function mock(OutputInterface $output, int $mocks)
    {
        $progress = new ProgressBar($output, $mocks);
        for ($i = 0; $i < $mocks; ++$i) {
            $progress->advance();
            // These will work with real or mocked queues.
            $this->queue->enqueue(new QueueItemMock('blog-article', 359325));
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
            $this->logger->warning('This is using mocked information.');
        }
        if ($mocks = $input->getOption('mock')) {
            $this->mock($output, $mocks);
        }
        $restTime = (int) $input->getOption('interval');
        $restTime = $restTime < 1 ? 10 : $restTime;
        $timeout = (int) $input->getOption('timeout');
        $maxIterations = $input->getOption('iterations');
        $memoryCheckInterval = $input->getOption('memory-interval');
        $memoryThreshold = ($input->getOption('memory')) * 1000 * 1000;
        // Initial values.
        $startTime = time();
        $iterations = 0;
        // Loop.
        while (true) {
            ++$iterations;
            if ($iterations % $memoryCheckInterval === 0) {
                $memory = memory_get_usage();
                $this->logger->debug('Memory usage at '.memory_get_usage());
                if ($memory > $memoryThreshold) {
                    $this->logger->error('Memory limit reached, stopping script.', [
                        'limit' => $memoryThreshold,
                        'memory' => $memory,
                        'interval' => $memoryCheckInterval,
                    ]);
                    break;
                }
            }
            if ($iterations === $maxIterations) {
                $this->logger->warning('Max iterations reached, stopping script.', [
                    'iterations' => $iterations,
                    'maxIterations' => $maxIterations,
                ]);
                break;
            }
            if (time() - $startTime >= $timeout) {
                $this->logger->warning('Max time reached, stopping script.', [
                    'time' => time() - $startTime,
                    'timeout' => $timeout,
                ]);
                break;
            }
            $next = $this->loop($input);

            if (!$next) {
                sleep($restTime);
            }
        }
    }

    public function transform(QueueItem $item)
    {
        $entity = null;
        try {
            // Transform into something for gearman.
            $entity = $this->transformer->transform($item);
        } catch (BadResponse $e) {
            // We got a 404 or server error.
            $this->logger->error("Item does not exist in API: {$item->getType()} ({$item->getId()})", [
                'exception' => $e,
                'item' => $item,
            ]);
            // Remove from queue.
            $this->queue->commit($item);
        } catch (Throwable $e) {
            // Unknown error.
            $this->logger->error("There was an unknown problem importing {$item->getType()} ({$item->getId()})", [
                'exception' => $e,
                'item' => $item,
            ]);
            // Remove from queue.
            $this->queue->commit($item);
        }

        return $entity;
    }

    public function loop(InputInterface $input)
    {
        $this->logger->debug('Loop start... [');
        $timeout = $input->getOption('queue-timeout');
        $this->logger->debug('-> Listening to topic ', ['topic' => $this->topic]);
        if ($this->queue->isValid()) {
            $item = $this->queue->dequeue($timeout);
            if ($entity = $this->transform($item, $this->logger)) {
                // Grab the gearman task.
                $gearmanTask = $this->transformer->getGearmanTask($item);
                // Run the task.
                $this->logger->info('-> Running gearman task', [
                    'gearmanTask' => $gearmanTask,
                    'type' => $item->getType(),
                    'id' => $item->getId(),
                ]);
                // Set the task to go.
                $this->client->doLow($gearmanTask, $entity, md5($item->getReceipt()));
                // Commit.
                $this->queue->commit($item);
                $this->logger->info('-> Committed task', [
                    'gearmanTask' => $gearmanTask,
                    'type' => $item->getType(),
                    'id' => $item->getId(),
                ]);
            }
        } else {
            $this->logger->debug('-> Queue is empty ', ['topic' => $this->topic]);
        }
        $this->logger->debug("]\n");

        return $this->queue->isValid();
    }
}
