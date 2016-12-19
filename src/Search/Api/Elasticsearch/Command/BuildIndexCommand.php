<?php

namespace eLife\Search\Api\Elasticsearch\Command;

use eLife\Search\Api\Elasticsearch\ElasticsearchClient;
use eLife\Search\Api\Elasticsearch\Response\SuccessResponse;
use eLife\Search\Workflow\CliLogger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use Throwable;

class BuildIndexCommand extends Command
{
    private $client;
    private $logger;

    public function __construct(ElasticsearchClient $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;

        parent::__construct(null);
    }

    protected function configure()
    {
        $this
            ->setName('search:setup')
            ->setDescription('Re-index elasticsearch <comment>WARNING: DROPS CONTENT WITH -d</comment>')
            ->addOption('delete', 'd', InputOption::VALUE_OPTIONAL, 'Drop content', false)
            ->setHelp('Creates new Gearman client and imports entities from API');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $toDelete = $input->getParameterOption('delete');

        $logger = new CliLogger($input, $output, $this->logger);

        $mapping = array_filter(
            array_merge(
                [],
                Yaml::parse(file_get_contents(__DIR__.'/resources/_default_.yaml')),
                Yaml::parse(file_get_contents(__DIR__.'/resources/article.yaml')),
                Yaml::parse(file_get_contents(__DIR__.'/resources/blog-article.yaml')),
                Yaml::parse(file_get_contents(__DIR__.'/resources/event.yaml')),
                Yaml::parse(file_get_contents(__DIR__.'/resources/interview.yaml')),
                Yaml::parse(file_get_contents(__DIR__.'/resources/labs-experiment.yaml'))
            ), 'json_encode');

        $config = [
            'client' => ['ignore' => [400, 404]],
            'body' => [
                'mappings' => $mapping,
            ],
        ];

        $delete = null;
        $create = null;

        // Try removing old one.
        if ($toDelete) {
            try {
                $delete = $this->client->deleteIndex();
            } catch (Throwable $e) {
                $logger->debug($e->getMessage(), $e->getTrace());
            }
            if ($delete['payload'] instanceof SuccessResponse) {
                $logger->info('Removed previous index');
            }
        }
        // Try adding new one!
        try {
            $create = $this->client->customIndex($config);
        } catch (Throwable $e) {
            $logger->error($e->getMessage(), $e->getTrace());
        }
        if ($create['payload'] instanceof SuccessResponse) {
            $logger->info('Created new index <comment>[Don\'t forget to re-index!]</comment>');
        }
        if (isset($create['error'])) {
            $logger->warning('Index '.$create['error']['reason'].' skipping creation.');
        }
    }
}
