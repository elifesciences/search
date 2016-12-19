<?php

namespace eLife\Search;

use Closure;
use eLife\Search\Annotation\Register;
use eLife\Search\Api\Elasticsearch\ElasticsearchClient;
use eLife\Search\Api\Elasticsearch\Response\DocumentResponse;
use eLife\Search\Api\Elasticsearch\Response\SuccessResponse;
use eLife\Search\Api\Response\BlogArticleResponse;
use eLife\Search\Workflow\CliLogger;
use Exception;
use GuzzleHttp\Client;
use LogicException;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Process\Process;
use ZipArchive;

/**
 * @property LoggerInterface temp_logger
 * @property float|int previousProgress
 * @property ProgressBar progress
 */
final class Console
{
    /**
     * These commands map to [name]Command so when the command "hello" is configured
     * it will call helloCommand() on this class with InputInterface and OutputInterface
     * as parameters.
     *
     * This will hopefully cover most things.
     */
    public static $quick_commands = [
        'hello' => ['description' => 'This is a quick hello world command'],
        'echo' => ['description' => 'Example of asking a question'],
        'cache:clear' => ['description' => 'Clears cache'],
        'debug:params' => ['description' => 'Lists current parameters'],
        'debug:search' => ['description' => 'Test command for debugging elasticsearch'],
        'debug:search:random' => ['description' => 'Test command for debugging elasticsearch'],
        'spawn' => [
            'description' => 'WARNING: Experimental, may create child processes.',
            'args' => [
                ['name' => 'cmd', 'default' => 'list'],
                ['name' => 'amount', 'default' => 1],
                ['name' => 'memory', 'default' => 40],
            ],
        ],
    ];

    public function __construct(Application $console, Kernel $app)
    {
        $this->console = $console;
        $this->app = $app;
        $this->root = __DIR__.'/../..';

        // Some annotations
        Register::registerLoader();

        $this->console->getDefinition()->addOption(new InputOption('--env', '-e', InputOption::VALUE_REQUIRED, 'The Environment name.', 'dev'));

        // Add commands from the DI container. (for more complex commands.)
        if (GEARMAN_INSTALLED) {
            $this->console->addCommands([
                $app->get('console.gearman.worker'),
                $app->get('console.gearman.client'),
                $app->get('console.gearman.queue'),
                $app->get('console.build_index'),
            ]);
        }
        $this->logger = $app->get('logger');
    }

    private function path($path = '')
    {
        return $this->root.$path;
    }

    public function getElasticClient() : ElasticsearchClient
    {
        return $this->app->get('elastic.client');
    }

    /**
     * @deprecated
     */
    public function ramlProgressCallback($download_size, $downloaded_size, $upload_size, $uploaded_size)
    {
        if ($download_size == 0) {
            $progress = 0;
        } else {
            $progress = round($downloaded_size * 100 / $download_size);
        }

        if ($progress > $this->previousProgress) {
            $this->progress->advance();
            $this->previousProgress = $progress;
        }
    }

    /**
     * @deprecated
     */
    public function ramlCommand(InputInterface $input, OutputInterface $output, LoggerInterface $logger)
    {
        $commit_ref = trim(file_get_contents(__DIR__.'/../../.apiversion'));
        $zip = __DIR__.'/../../cache/raml--'.$commit_ref.'.zip';
        $target = __DIR__.'/../../tests/raml/';

        $this->progress = new ProgressBar($output, 100);

        if (!file_exists($zip) && filesize($zip) > 0) {
            $logger->debug('Downloading...');
            $client = new Client([
                'progress' => [$this, 'ramlProgressCallback'],
                'save_to' => $zip,
            ]);
            $response = $client->get('https://github.com/elifesciences/api-raml/archive/'.$commit_ref.'.zip');
            $response->getBody()->getSize();
            $this->progress->finish();
            // Fix progress bug.
            $logger->info(' - '.$response->getBody()->getSize().' bytes downloaded.');
        }

        $logger->debug('Extracting JSON files...');
        $archive = new ZipArchive();
        if ($archive->open($zip) === true) {
            // Grab folder name from first item in index.
            $folderName = $archive->getNameIndex(0);
            // Remove old target.
            exec('rm -rf '.$target);
            $progress = new ProgressBar($output, $archive->numFiles);
            $json = 0;
            // Loop through files in ZIP File.
            for ($i = 0; $i < $archive->numFiles; ++$i) {
                $progress->advance();
                $filename = $archive->getNameIndex($i);
                // Only unzip json files from dist and put them in place.
                if (strpos($filename, '.json') !== false && strpos($filename, '/dist/') !== false) {
                    ++$json;
                    $archive->extractTo($target, array($archive->getNameIndex($i)));
                }
            }
            // Fix progress bar bug.
            $logger->info(' - copied '.$json.' files');
            // Clean up folder structure.
            exec('mv '.$target.$folderName.'dist/* '.$target.' && rm -rf '.$target.$folderName);
            $archive->close();
        } else {
            $logger->error('Something went wrong while unzipping file.');
        }
    }

    protected function responseFromArray($className, $data)
    {
        return $this->app->get('serializer')->deserialize(json_encode($data), $className, 'json');
    }

    public function debugSearchRandomCommand(InputInterface $input, OutputInterface $output, LoggerInterface $logger)
    {
        $elastic = $this->getElasticClient();

        $blog = $this->responseFromArray(BlogArticleResponse::class, [
            'id' => '12456'.rand(0, 10000),
            'title' => 'some blog article',
            'impactStatement' => 'Something impacting in a statement like fashion.',
            'published' => '2016-06-09T15:15:10+00:00',
        ]);
        $inserting = $elastic->indexDocument('test', rand(0, 10000), $blog);
        if ($inserting instanceof SuccessResponse) {
            $logger->info('Document inserted!');
        }
    }

    public function debugSearchCommand(InputInterface $input, OutputInterface $output, LoggerInterface $logger)
    {
        $elastic = $this->getElasticClient();

        $insert = $elastic->createIndex();
        if ($insert instanceof SuccessResponse) {
            $logger->info('Index created');
        } else {
            $logger->info('Index was no created');
        }

        $blog = $this->responseFromArray(BlogArticleResponse::class, [
            'id' => '12456',
            'title' => 'some blog article',
            'impactStatement' => 'Something impacting in a statement like fashion.',
            'published' => '2016-06-09T15:15:10+00:00',
        ]);
        $inserting = $elastic->indexDocument('test', 1, $blog);
        if ($inserting instanceof SuccessResponse) {
            $logger->info('Document inserted!');
        }

        $doc = $elastic->getDocumentById('test', 1);
        if ($doc instanceof DocumentResponse) {
            $document = $doc->unwrap();
            $logger->info('Document `'.$document->title.'` was requested!');
        }

        $del = $elastic->deleteDocument('test', 1);
        if ($del instanceof SuccessResponse) {
            $logger->info('Document was deleted!');
        }
    }

    public function spawnCommand(InputInterface $input, OutputInterface $output, LoggerInterface $logger)
    {
        $command = (string) $input->getArgument('cmd');
        $amount = (int) $input->getArgument('amount');
        // $memory = ((int)$input->getArgument('memory')) * 1024 * 1024; // Not working currently.
        $processes = [];
        for ($i = 0; $i < $amount; ++$i) {
            $processes[] = new Process('exec php '.$this->path('/bin/console').' '.$command.' --ansi');
        }
        $pids = [];
        $running = true;
        while (count($processes) > 0) {
            if ($running) {
                foreach ($processes as $i => $process) {
                    /** @var $process Process */
                    if (!$process->isStarted()) {
                        $process->start();
                        $pids[$i] = $process->getPid();
                        $logger->warning('Process starts, PID:'.$process->getPid());
                    }

                    $output->write($process->getIncrementalOutput());
                    $output->write($process->getIncrementalErrorOutput());

                    if (!$process->isRunning()) {
                        $process->restart();
                        $logger->error('Process stopped (Memory: '.round(memory_get_usage() / 1024 / 1024, 2).'Mb)');
                        $logger->warning('Starting new process');
                        $processes[] = new Process('exec php '.$this->path('/bin/console').' '.$command.' --ansi');
                        unset($processes[$i]);
                    }
                }
            }
        }
        sleep(1);
    }

    public function debugParamsCommand(InputInterface $input, OutputInterface $output, LoggerInterface $logger)
    {
        foreach ($this->app->get('config') as $key => $config) {
            if (is_array($config)) {
                $logger->warning($key);
                $logger->info(json_encode($config, JSON_PRETTY_PRINT));
                $logger->debug(' ');
            } elseif (is_bool($config)) {
                $logger->warning($key);
                $logger->info($config ? 'true' : 'false');
                $logger->debug(' ');
            } else {
                $logger->warning($key);
                $logger->info($config);
                $logger->debug(' ');
            }
        }
    }

    public function cacheClearCommand(InputInterface $input, OutputInterface $output, LoggerInterface $logger)
    {
        $logger->warning('Clearing cache...');
        try {
            exec('rm -rf '.$this->root.'/cache/*');
        } catch (Exception $e) {
            $logger->error($e);
        }
        $logger->info('Cache cleared successfully.');
    }

    public function echoCommand(InputInterface $input, OutputInterface $output, LoggerInterface $logger)
    {
        $question = new Question('<question>Are we there yet?</question> ');
        $helper = new QuestionHelper();
        while (true) {
            $name = $helper->ask($input, $output, $question);
            if ($name === 'yes') {
                break;
            }
            $logger->error($name);
        }
    }

    public function helloCommand(InputInterface $input, OutputInterface $output, LoggerInterface $logger)
    {
        $logger->info('Hello from the outside (of the global scope)');
        $logger->debug('This is working');
    }

    public function run($input = null, $output = null)
    {
        foreach (self::$quick_commands as $name => $cmd) {
            if (strpos($name, ':')) {
                $pieces = explode(':', $name);
                $first = array_shift($pieces);
                $pieces = array_map('ucfirst', $pieces);
                array_unshift($pieces, $first);
                $fn = implode('', $pieces);
            } else {
                $fn = $name;
            }
            if (!method_exists($this, $fn.'Command')) {
                throw new LogicException('Your command does not exist: '.$fn.'Command');
            }
            // Hello
            $command = $this->console
                ->register($name)
                ->setDescription($cmd['description'] ?? $name.' command')
                ->setCode(Closure::bind(function (InputInterface $input, OutputInterface $output) use ($fn, $name) {
                    $logger = new CliLogger($input, $output, $this->logger);
                    $this->{$fn.'Command'}($input, $output, $logger);
                }, $this));

            if (isset($cmd['args'])) {
                foreach ($cmd['args'] as $arg) {
                    $command->addArgument($arg['name'], $arg['mode'] ?? null, $arg['description'] ?? '', $arg['default'] ?? null);
                }
            }
        }
        $this->console->run($input, $output);
    }
}
