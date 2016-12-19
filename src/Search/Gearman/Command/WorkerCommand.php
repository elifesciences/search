<?php

namespace eLife\Search\Gearman\Command;

use eLife\ApiSdk\ApiSdk;
use eLife\Search\Annotation\GearmanTaskDriver;
use eLife\Search\Api\ApiValidator;
use eLife\Search\Api\Elasticsearch\ElasticsearchClient;
use eLife\Search\Workflow\BlogArticleWorkflow;
use eLife\Search\Workflow\CliLogger;
use eLife\Search\Workflow\CollectionWorkflow;
use eLife\Search\Workflow\EventWorkflow;
use eLife\Search\Workflow\InterviewWorkflow;
use eLife\Search\Workflow\LabsExperimentWorkflow;
use eLife\Search\Workflow\PodcastEpisodeWorkflow;
use eLife\Search\Workflow\ResearchArticleWorkflow;
use JMS\Serializer\Serializer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class WorkerCommand extends Command
{
    private $sdk;
    private $serializer;
    private $gearman;
    private $client;
    private $validator;
    private $logger;

    public function __construct(
        ApiSdk $sdk,
        Serializer $serializer,
        GearmanTaskDriver $gearman,
        ElasticsearchClient $client,
        ApiValidator $validator,
        LoggerInterface $logger
    ) {
        $this->sdk = $sdk;
        $this->serializer = $serializer;
        $this->gearman = $gearman;
        $this->client = $client;
        $this->validator = $validator;
        $this->logger = $logger;

        parent::__construct(null);
    }

    protected function configure()
    {
        $this
            ->setName('gearman:worker')
            ->setDescription('Creates new Gearman workers.')
            ->setHelp('This command will spin up a new gearman worker based on the options you provide. By default this will be with all jobs available')
            ->addArgument('id', InputArgument::OPTIONAL, 'Identifier to distinguish workers from each other');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = new CliLogger($input, $output, $this->logger);
        // Working..
        $this->gearman->registerWorkflow(new BlogArticleWorkflow($this->sdk->getSerializer(), $logger, $this->client, $this->validator));
        $this->gearman->registerWorkflow(new EventWorkflow($this->sdk->getSerializer(), $logger, $this->client, $this->validator));
        $this->gearman->registerWorkflow(new InterviewWorkflow($this->sdk->getSerializer(), $logger, $this->client, $this->validator));
        $this->gearman->registerWorkflow(new ResearchArticleWorkflow($this->sdk->getSerializer(), $logger, $this->client, $this->validator));
        $this->gearman->registerWorkflow(new LabsExperimentWorkflow($this->sdk->getSerializer(), $logger, $this->client, $this->validator));
        $this->gearman->registerWorkflow(new PodcastEpisodeWorkflow($this->sdk->getSerializer(), $logger, $this->client, $this->validator));
        $this->gearman->registerWorkflow(new CollectionWorkflow($this->sdk->getSerializer(), $logger, $this->client, $this->validator));
        $this->gearman->work();
    }
}
