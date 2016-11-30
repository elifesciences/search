<?php

namespace eLife\Search\Gearman\Command;

use eLife\ApiSdk\ApiSdk;
use eLife\Search\Workflow\CliLogger;
use Error;
use GearmanClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Traversable;

final class ApiSdkCommand extends Command
{
    private static $supports = ['all', 'BlogArticles', 'Events', 'Interviews', 'LabsExperiments', 'PodcastEpisodes', 'Collections', 'ResearchArticles'];

    private $client;
    private $sdk;
    private $serializer;
    private $output;

    public function __construct(
        ApiSdk $sdk,
        GearmanClient $client
    ) {
        $this->serializer = $sdk->getSerializer();
        $this->sdk = $sdk;
        $this->client = $client;
        parent::__construct(null);
    }

    protected function configure()
    {
        $this
            ->setName('gearman:import')
            ->setDescription('Import items from API.')
            ->setHelp('Creates new Gearman client and imports entities from API')
            ->addArgument('entity', InputArgument::REQUIRED, 'Must be one of the following <comment>['.implode(', ', self::$supports).']</comment>');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = new CliLogger($input, $output);
        $this->output = $output;
        $entity = $input->getArgument('entity');
        // Only the configured.
        if (!in_array($entity, self::$supports)) {
            $logger->error('Entity with name '.$entity.' not supported.');

            return;
        }
        if ($entity === 'all') {
            foreach (self::$supports as $e) {
                if ($e !== 'all') {
                    // Run the item.
                    $this->{'import'.$e}($logger);
                }
            }
        } else {
            // Run the item.
            $this->{'import'.$entity}($logger);
        }
        // Reporting.
        $logger->info("\nAll entities queued.");
    }

    public function importPodcastEpisodes(LoggerInterface $logger)
    {
        $episodes = $this->sdk->podcastEpisodes();
        $this->iterateSerializeTask($episodes, $logger, 'podcast_episode_validate');
    }

    public function importCollections(LoggerInterface $logger)
    {
        $logger->info('Importing Collections');
        $collections = $this->sdk->collections();
        $this->iterateSerializeTask($collections, $logger, 'collection_validate', $collections->count());
    }

    public function importLabsExperiments(LoggerInterface $logger)
    {
        $logger->info('Importing Labs Experiments');
        $events = $this->sdk->labsExperiments();
        $this->iterateSerializeTask($events, $logger, 'labs_experiment_validate', $events->count());
    }

    public function importResearchArticles(LoggerInterface $logger)
    {
        $logger->info('Importing Research Articles');
        $events = $this->sdk->articles();
        $this->iterateSerializeTask($events, $logger, 'research_article_validate', $events->count());
    }

    public function importInterviews(LoggerInterface $logger)
    {
        $logger->info('Importing Interviews');
        $events = $this->sdk->interviews();
        $this->iterateSerializeTask($events, $logger, 'interview_validate', $events->count());
    }

    public function importEvents(LoggerInterface $logger)
    {
        $logger->info('Importing Events');
        $events = $this->sdk->events();
        $this->iterateSerializeTask($events, $logger, 'event_validate', $events->count());
    }

    public function importBlogArticles(LoggerInterface $logger)
    {
        $logger->info('Importing Blog Articles');
        $articles = $this->sdk->blogArticles();
        $this->iterateSerializeTask($articles, $logger, 'blog_article_validate', $articles->count());
    }

    private function iterateSerializeTask(Traversable $items, LoggerInterface $logger, string $task, int $count = 0)
    {
        $progress = new ProgressBar($this->output, $count);
        foreach ($items as $item) {
            $progress->advance();
            try {
                $title = method_exists($item, 'getTitle') ? $item->getTitle() : ' a new '.get_class($item);
                // @todo remove temporary import fix.
                if (
                    trim($title) !== 'Mapping the zoonotic niche of Ebola virus disease in Africa' &&
                    trim($title) !== 'The genome sequence of the colonial chordate, <i>Botryllus schlosseri</i>'
                ) {
                    $normalized = $this->serializer->serialize($item, 'json');
                    $this->task($task, $normalized);
                }
            } catch (Throwable $e) {
                $logger->alert("Error on a ".get_class($item), ['exception' => $e]);
                continue;
            }
        }
        $progress->finish();
        $progress->clear();
    }

    private function task($item, ...$data)
    {
        $this->client->doLow($item, ...$data);
    }
}
