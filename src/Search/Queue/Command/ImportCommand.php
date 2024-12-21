<?php

namespace eLife\Search\Queue\Command;

use DateTimeImmutable;
use eLife\ApiSdk\ApiSdk;
use eLife\ApiSdk\Collection\Sequence;
use eLife\Bus\Limit\Limit;
use eLife\Bus\Queue\InternalSqsMessage;
use eLife\Bus\Queue\WatchableQueue;
use eLife\Logging\Monitoring;
use Generator;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

final class ImportCommand extends Command
{
    /** @var array<string> $supports */
    private static array $supports = [
        'all',
        'BlogArticles',
        'Interviews',
        'LabsPosts',
        'PodcastEpisodes',
        'Collections',
        'ResearchArticles',
        'ReviewedPreprints',
    ];

    private OutputInterface $output;
    private DateTimeImmutable|null $dateFrom = null;
    private string|null $useDate = null;
    private int|null $applyLimit = null;

    public function __construct(
        private ApiSdk $sdk,
        private WatchableQueue $queue,
        private LoggerInterface $logger,
        private Monitoring $monitoring,
        private Limit $limit,
    ) {
        parent::__construct(null);
    }

    protected function configure()
    {
        $this
            ->setName('queue:import')
            ->setDescription('Import items from API.')
            ->setHelp('Lists entities from API and enqueues them')
            ->addArgument(
                'entity',
                InputArgument::REQUIRED,
                'Must be one of the following <comment>['.implode(', ', self::$supports).']</comment>'
            )
            ->addOption('dateFrom', '-d', InputOption::VALUE_OPTIONAL, 'Start date filter')
            ->addOption('useDate', '-u', InputOption::VALUE_OPTIONAL, 'Use date filter')
            ->addOption('limit', '-l', InputOption::VALUE_OPTIONAL, 'Limit items to import per entity');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $entity = $input->getArgument('entity');
        // Only the configured.
        if (!in_array($entity, self::$supports)) {
            $this->logger->error('Entity with name '.$entity.' not supported.');

            return 1;
        }

        if ($input->getOption('dateFrom') !== null) {
            $this->dateFrom = DateTimeImmutable::createFromFormat(DATE_ATOM, $input->getOption('dateFrom'));
        }

        if ($input->getOption('useDate') !== null) {
            $this->useDate = $input->getOption('useDate');
        }

        if ($input->getOption('limit') !== null) {
            $this->applyLimit = (int) $input->getOption('limit');
        }

        try {
            $this->monitoring->startTransaction();
            $this->monitoring->nameTransaction('queue:import');
            if ('all' === $entity) {
                foreach (self::$supports as $e) {
                    if ('all' !== $e) {
                        // Run the item.
                        $output->writeln('<comment>'.$e.'</comment>');
                        $this->{'import'.$e}();
                    }
                }
            } else {
                // Run the item.
                $this->{'import'.$entity}();
            }
            // Reporting.
            $this->logger->info("\nAll entities queued.");
            $this->monitoring->endTransaction();
        } catch (Throwable $e) {
            $this->logger->error('Error in import', ['exception' => $e]);
            $this->monitoring->recordException($e, 'Error in import');
            throw $e;
        }

        return 0;
    }

    public function importPodcastEpisodes()
    {
        $this->logger->info('Importing PodcastEpisodes');
        $episodes = $this->sdk->podcastEpisodes();
        $this->iterateSerializeTask($episodes, 'podcast-episode', 'getNumber', $episodes->count());
    }

    public function importCollections()
    {
        $this->logger->info('Importing Collections');
        $collections = $this->sdk->collections();
        $this->iterateSerializeTask($collections, 'collection', 'getId', $collections->count());
    }

    public function importLabsPosts()
    {
        $this->logger->info('Importing Labs Posts');
        $events = $this->sdk->labsPosts();
        $this->iterateSerializeTask($events, 'labs-post', 'getId', $events->count());
    }

    public function importResearchArticles()
    {
        $this->logger->info('Importing Research Articles');
        $events = $this->sdk->articles();
        $this->iterateSerializeTask($events, 'article', 'getId', $events->count(), $skipInvalid = true);
    }

    public function importReviewedPreprints()
    {
        $this->logger->info('Importing Reviewed Preprints');
        $events = $this->sdk->reviewedPreprints();

        if (!is_null($this->useDate)) {
            $events = $events->useDate($this->useDate);
        }

        if (!is_null($this->dateFrom)) {
            $events = $events->startDate($this->dateFrom);
        }

        $this->iterateSerializeTask($events, 'reviewed-preprint', 'getId', $events->count(), $skipInvalid = true);
    }

    public function importInterviews()
    {
        $this->logger->info('Importing Interviews');
        $events = $this->sdk->interviews();
        $this->iterateSerializeTask($events, 'interview', 'getId', $events->count());
    }

    public function importBlogArticles()
    {
        $this->logger->info('Importing Blog Articles');
        $articles = $this->sdk->blogArticles();
        $this->iterateSerializeTask($articles, 'blog-article', 'getId', $articles->count());
    }

    private function iterateSerializeTask(
        Sequence $items,
        string $type,
        $method = 'getId',
        int $count = 0,
        $skipInvalid = false
    )
    {
        $total = $this->applyLimit ?? $count;
        $this->logger->info(sprintf('Importing %d items of type %s', $total, $type));
        $progress = new ProgressBar($this->output, $total);
        $limit = $this->limit;

        // lazy iterate here instead of relying on the SDK methods
        foreach ($this->lazySlices($items) as $item) {
            if ($limit->hasBeenReached()) {
                throw new RuntimeException('Command cannot complete because: '.implode(', ', $limit->getReasons()));
            }
            $progress->advance();
            try {
                $this->enqueue($type, $item->$method());
            } catch (Throwable $e) {
                $item = $item ?? null;
                $this->logger->error('Skipping import on a '.get_class($item), ['exception' => $e]);
                $this->monitoring->recordException($e, 'Skipping import on a '.get_class($item));
            }
        }
        $progress->finish();
        $progress->clear();
    }

    private function lazySlices(Sequence $items): Generator
    {
        // create 100-item slices
        $total = $this->applyLimit ?? $items->count();
        $sliceStart = 0;
        $sliceSize = 100;
        $count = 0;
        while ($total >= $sliceStart) {
            foreach ($items->slice($sliceStart, $sliceSize)->toArray() as $item) {
                if ($this->applyLimit !== null) {
                    if ($count >= $this->applyLimit) {
                        break 2;
                    }
                    $count += 1;
                }

                // create a Generator to iterate over items in a slice
                yield $item;
            }
            $sliceStart += $sliceSize;
        }
    }

    private function enqueue($type, $identifier)
    {
        $this->logger->info("Item ($type, $identifier) being enqueued");
        $item = new InternalSqsMessage($type, $identifier);
        $this->queue->enqueue($item);
        $this->logger->info("Item ($type, $identifier) enqueued successfully");
    }
}
