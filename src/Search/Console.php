<?php

namespace eLife\Search;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

$console = new Application('eLife Search API', '1.0.0');
$console->getDefinition()->addOption(new InputOption('--env', '-e', InputOption::VALUE_REQUIRED, 'The Environment name.', 'dev'));
// Hello
$console
    ->register('hello')
    ->setDescription('This is a hello world command.')
    ->setCode(function (InputInterface $input, OutputInterface $output) {
        $output->writeln('Hello world');
    })
;

return $console;
