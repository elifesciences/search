<?php

namespace eLife\Search\Gearman\Command;

use eLife\ApiSdk\ApiSdk;
use eLife\Search\Annotation\GearmanTaskDriver;
use eLife\Search\Workflow\BlogArticleWorkflow;
use eLife\Search\Workflow\CliLogger;
use JMS\Serializer\Serializer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class WorkerCommand extends Command
{
    private $sdk;
    private $serializer;
    private $gearman;

    public function __construct(
        ApiSdk $sdk,
        Serializer $serializer,
        GearmanTaskDriver $gearman
    ) {
        $this->sdk = $sdk;
        $this->serializer = $serializer;
        $this->gearman = $gearman;
        parent::__construct(null);
    }

    protected function configure()
    {
        $this
            ->setName('gearman:worker')
            ->setDescription('Creates new Gearman workers.')
            ->setHelp('This command will spin up a new gearman worker based on the options you provide. By default this will be with all jobs available');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = new CliLogger($input, $output);
        $this->gearman->registerWorkflow(new BlogArticleWorkflow($this->sdk->getSerializer(), $logger));
        $this->gearman->work($logger);
    }
}
