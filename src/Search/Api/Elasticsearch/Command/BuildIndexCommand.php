<?php

namespace eLife\Search\Api\Elasticsearch\Command;

use eLife\Search\Api\Elasticsearch\PlainElasticsearchClient;
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

    public function __construct(PlainElasticsearchClient $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;

        parent::__construct(null);
    }

    protected function configure()
    {
        $this
            ->setName('search:setup')
            ->setDescription('Ensure Elasticsearch has been setup <comment>WARNING: DROPS CONTENT WITH -d</comment>')
            ->addOption('delete', 'd', InputOption::VALUE_NONE, 'Drop content')
            ->addOption('index', 'i', InputOption::VALUE_OPTIONAL, 'Index that should be (re)created')
            ->setHelp('Creates new Gearman client and imports entities from API');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!empty($input->getOption('index'))) {
            $this->client->defaultIndex($input->getOption('index'));
        }

        $toDelete = $input->getOption('delete');

        $mapping = array_filter(
            array_merge(
                [],
                Yaml::parse(file_get_contents(__DIR__.'/resources/mappings.yaml'))
            ), 'json_encode');

        $config = [
            'client' => ['ignore' => [404]],
            'body' => [
                'mappings' => $mapping,
                'settings' => [
                    'max_result_window' => 20000,
                ],
            ],
        ];

        $delete = null;

        try {
            if ($toDelete && $this->client->indexExists()) {
                $this->client->deleteIndex();
                $this->logger->info("Removed previous index {$this->client->index()}");
            }

            if (!$this->client->indexExists()) {
                $this->client->createIndex($index = null, $config);
                $this->logger->info("Created new empty index {$this->client->index()}");
            } else {
                $this->logger->warning("Index {$this->client->index()} already exists, skipping creation.");
            }
        } catch (Throwable $e) {
            $this->logger->error("Cannot (re)create ElasticSearch index {$this->client->index()}", ['exception' => $e]);
            throw $e;
        }
    }
}
