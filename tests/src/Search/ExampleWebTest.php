<?php

namespace tests\eLife\Search;

use eLife\Search\Api\Elasticsearch\ElasticsearchClient;
use eLife\Search\Console;
use eLife\Search\Kernel;
use Psr\Log\NullLogger;
use Silex\WebTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @group failing
 */
class ExampleWebTest extends WebTestCase
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
        if (file_exists(__DIR__.'/../../../config/local.php')) {
            $this->isLocal = true;
            $config = include __DIR__.'/../../../config/local.php';
        } else {
            $this->isLocal = false;
            $config = include __DIR__.'/../../../config/ci.php';
        }
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
    public function testCanRunCommand()
    {
        // Run command
        $lines = $this->runCommand('hello');
        // And test.
        $this->assertEquals('Hello from the outside (of the global scope)', $lines[0]);
        $this->assertEquals('This is working', $lines[1]);
    }

    public function getElasticSearchClient() : ElasticsearchClient
    {
        return $this->kernel->get('elastic.client');
    }

    /**
     * @test
     */
    public function testElasticSearchIndex()
    {
        $client = $this->getElasticSearchClient();
        $client->deleteIndex();
        $this->runCommand('search:setup');
        $client->indexJsonDocument('research-article', '19662', '
        {
            "status": "vor",
            "volume": 5,
            "doi": "10.7554/eLife.19662",
            "type": "research-article",
            "copyright": {
                "holder": "Srinivasan et al",
                "statement": "This article is distributed under the terms of the Creative Commons Attribution License, which permits unrestricted use and redistribution provided that the original author and source are credited.",
                "license": "CC-BY-4.0"
            },
            "impactStatement": "Extracellular actin is an evolutionarily-conserved signal of tissue injury that is recognised in the fruit fly via similar machinery as reported in vertebrates.",
            "title": "Actin is an evolutionarily-conserved damage-associated molecular pattern that signals tissue injury in <i>Drosophila melanogaster</i>",
            "authorLine": "Naren Srinivasan et al",
            "versionDate": "2016-12-19T12:31:04Z",
            "researchOrganisms": [
                "D. melanogaster"
            ],
            "version": 3,
            "published": "2016-11-18T00:00:00Z",
            "statusDate": "2016-12-05T16:36:45Z",
            "pdf": "https://publishing-cdn.elifesciences.org/19662/elife-19662-v3.pdf",
            "subjects": [
                {
                    "id": "immunology",
                    "name": "Immunology"
                }
            ],
            "elocationId": "e19662",
            "id": "19662",
            "stage": "published"
        }
        ', true);

        $api = $this->createClient();
        $api->request('GET', '/search');
        $response = $api->getResponse();
        $this->assertTrue($response->isOk());
        $json = json_decode($response->getContent());

        $this->assertEquals($json->total, 1);
        $this->assertEquals($json->items[0]->status, 'vor');
        // ...
        $client->deleteIndex();
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
