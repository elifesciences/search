<?php

namespace src\Search\Gearman\Command;

use eLife\Search\Gearman\Command\ImportCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ImportCommandTest extends \PHPUnit_Framework_TestCase
{
    public function import()
    {
        $app = new Application();
        $importCommandMock = $this->getMockBuilder(ImportCommand::class)
            ->disableOriginalConstructor()
            ->getMock();
        $app->addCommands([$importCommandMock]);
        $command = $app->find('queue:import');
        $c = new CommandTester($command);
        $c->execute(['--dateFrom' => '2 days ago']);
    }
}