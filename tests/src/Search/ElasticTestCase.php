<?php

namespace tests\eLife\Search;

use eLife\Search\Api\Elasticsearch\ElasticsearchClient;
use eLife\Search\Console;
use eLife\Search\Kernel;
use Psr\Log\NullLogger;
use Silex\WebTestCase;
use Symfony\Component\BrowserKit\Client;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\HttpKernel\HttpKernelInterface;

abstract class ElasticTestCase extends WebTestCase
{
    protected $isLocal;
    protected $console;
    /** @var Kernel */
    protected $kernel;
    /** @var ElasticsearchClient */
    protected $client;
    /** @var Client */
    protected $api;

    public function getJsonResponse()
    {
        $response = $this->getResponse();
        if (!$response->isOk()) {
            $this->fail('Response returned was not 200');
        }
        $json = json_decode($response->getContent());

        return $json;
    }

    public function newClient()
    {
        return $this->api = static::createClient();
    }

    public function getResponse()
    {
        return $this->api->getResponse();
    }

    public function addDocumentToElasticSearch(string $doc)
    {
        $obj = json_decode($doc);
        $this->client->indexJsonDocument($obj->type, $obj->id, $doc, true);
    }
    public function addDocumentsToElasticSearch(array $docs)
    {
        foreach ($docs as $doc) {
            $this->addDocumentToElasticSearch($doc);
        }
    }

    public function createConfiguration()
    {
        if (file_exists(__DIR__.'/../../../config/local.php')) {
            $this->isLocal = true;
            $config = include __DIR__.'/../../../config/local.php';
        } else {
            $this->isLocal = false;
            $config = include __DIR__.'/../../../config/ci.php';
        }

        return $config;
    }

    protected function jsonRequest(string $verb, string $endpoint, array $params = array())
    {
        $headers = array(
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        );

        return $this->api->request(
            $verb,
            $endpoint,
            $params,
            [],
            $headers
        );
    }

    /**
     * Creates the application.
     *
     * @return HttpKernelInterface
     */
    public function createApplication()
    {
        $this->kernel = new Kernel($this->createConfiguration());

        return $this->kernel->getApp();
    }

    public function getElasticSearchClient(callable $fn = null): ElasticsearchClient
    {
        return $fn ? $fn($this->kernel->get('elastic.client')) : $this->kernel->get('elastic.client');
    }

    public function setUp()
    {
        parent::setUp();
        $this->client = $this->getElasticSearchClient();
        $lines = $this->runCommand('search:setup');
        if ($lines[0] === 'No alive nodes found in your cluster') {
            $this->markTestSkipped('Elasticsearch may not be installed, skipping');
        }
        $this->assertStringStartsWith('Created new index', $lines[0], 'Failed to run test during set up');
    }

    public function tearDown()
    {
        $this->client->deleteIndex();
        parent::tearDown();
    }

    public function runCommand(string $command)
    {
        $log = $this->returnCallback(function ($message) use (&$logs) {
            $logs[] = $message;
        });
        $logs = [];
        $logger = $this->createMock(NullLogger::class);

        foreach (['debug', 'info', 'alert', 'notice', 'error'] as $level) {
            $logger
                ->expects($this->any())
                ->method($level)
                ->will($log);
        }

        $app = new Application();
        $this->kernel->withApp(function ($app) use ($logger) {
            // Bug with silex?
            unset($app['logger']);
            $app['logger'] = function () use ($logger) {
                return $logger;
            };
        });
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
