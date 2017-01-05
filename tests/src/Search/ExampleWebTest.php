<?php
namespace tests\eLife\Search;

use eLife\Search\Console;
use eLife\Search\Kernel;
use Psr\Log\NullLogger;
use Silex\WebTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ExampleWebCase extends WebTestCase
{
    protected $isLocal;
    protected $console;
    /** @var Kernel */
    protected $kernel;

    /**
     * Creates the application.
     *
     * @return HttpKernelInterface
     */
    public function createApplication()
    {
        if (file_exists(__DIR__ . '/../../../config/local.php')) {
            $this->isLocal = true;
            $config = include __DIR__ . '/../../../config/local.php';
        } else {
            $this->isLocal = false;
            $config = include __DIR__ . '/../../../config/ci.php';
        }

        $config[''];
        $this->kernel = new Kernel($config);
        return $this->kernel->getApp();
    }

    /**
     * @test
     */
    public function testCan404()
    {
        $client = $this->createClient();
        $client->request('GET', '/');
        $this->assertTrue($client->getResponse()->isNotFound());
    }

    /**
     * @test
     */
    public function testCanRunCommand() {
        // Run command
        $lines = $this->runCommand('hello');
        // And test.
        $this->assertEquals('Hello from the outside (of the global scope)', $lines[0]);
        $this->assertEquals('This is working', $lines[1]);
    }

    public function testElasticSearchIndex() {
        $this->markTestSkipped('Just a theory');




    }

    public function runCommand(string $command)
    {
        $log = $this->returnCallback(function ($message) use (&$logs) {
            $logs[] = $message;
        });
        $logs = [];
        $logger = $this->createMock(NullLogger::class);

        foreach(['debug', 'info', 'alert', 'notice', 'error'] as $level) {
            $logger
                ->expects($this->any())
                ->method($level)
                ->will($log);
        }

        $app = new Application();
        $app->setAutoExit(false);
        $application = new Console($app, $this->kernel);
        $application->logger = $logger;

        $fp = tmpfile();
        $input = new StringInput($command);
        $output = new StreamOutput($fp);

        $application->run($input, $output);

        return $logs;
    }
}