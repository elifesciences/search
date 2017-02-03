<?php

namespace tests\eLife\Search\Web;

use eLife\Search\Api\Elasticsearch\ElasticsearchClient;
use eLife\Search\Console;
use eLife\Search\Kernel;
use Psr\Log\NullLogger;
use Silex\WebTestCase;
use Symfony\Component\BrowserKit\Client;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\HttpFoundation\Response;
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

    public function getArticleFixture(int $num)
    {
        switch ($num) {
            case 0:
                return [
                    'status' => 'poa',
                    'volume' => 5,
                    'doi' => '10.7554/eLife.15275',
                    'type' => 'research-article',
                    'version' => 1,
                    'copyright' => [
                        'holder' => 'Smith et al',
                        'statement' => 'This article is distributed under the terms of the Creative Commons Attribution License permitting unrestricted use and redistribution provided that the original author and source are credited.',
                        'license' => 'CC-BY-4.0',
                    ],
                    'title' => 'Mitochondrial support of persistent presynaptic vesicle mobilization with age-dependent synaptic growth after LTP',
                    'authorLine' => 'Heather L Smith et al',
                    'versionDate' => '2016-12-19T00:00:00Z',
                    'researchOrganisms' => [
                        'Rat',
                    ],
                    'published' => '2016-12-19T00:00:00Z',
                    'sortDate' => '2016-12-19T00:00:00Z',
                    'statusDate' => '2016-12-19T00:00:00Z',
                    'pdf' => 'https://publishing-cdn.elifesciences.org/15275/elife-15275-v1.pdf',
                    'subjects' => [
                        [
                            'id' => 'cell-biology',
                            'name' => 'Cell Biology',
                        ],
                        [
                            'id' => 'neuroscience',
                            'name' => 'Neuroscience',
                        ],
                    ],
                    'elocationId' => 'e15275',
                    'id' => '15275',
                    'stage' => 'published',
                ];
                break;
            case 1:
                return [
                    'status' => 'vor',
                    'volume' => 5,
                    'doi' => '10.7554/eLife.19662',
                    'type' => 'research-article',
                    'copyright' => [
                        'holder' => 'Srinivasan et al',
                        'statement' => 'This article is distributed under the terms of the Creative Commons Attribution License, which permits unrestricted use and redistribution provided that the original author and source are credited.',
                        'license' => 'CC-BY-4.0',
                    ],
                    'impactStatement' => 'Extracellular actin is an evolutionarily-conserved signal of tissue injury that is recognised in the fruit fly via similar machinery as reported in vertebrates.',
                    'title' => 'Actin is an evolutionarily-conserved damage-associated molecular pattern that signals tissue injury in <i>Drosophila melanogaster</i>',
                    'authorLine' => 'Naren Srinivasan et al',
                    'versionDate' => '2016-12-19T12:31:04Z',
                    'researchOrganisms' => [
                        'D. melanogaster',
                    ],
                    'version' => 3,
                    'published' => '2016-11-18T00:00:00Z',
                    'sortDate' => '2016-11-18T00:00:00Z',
                    'statusDate' => '2016-12-05T16:36:45Z',
                    'pdf' => 'https://publishing-cdn.elifesciences.org/19662/elife-19662-v3.pdf',
                    'subjects' => [
                        [
                            'id' => 'immunology',
                            'name' => 'Immunology',
                        ],
                    ],
                    'elocationId' => 'e19662',
                    'id' => '19662',
                    'stage' => 'published',
                ];
                break;
            case 2:
                return [
                    'status' => 'poa',
                    'volume' => 6,
                    'doi' => '10.7554/eLife.15276',
                    'type' => 'research-article',
                    'version' => 2,
                    'copyright' => [
                        'holder' => 'Smith et al',
                        'statement' => 'This article is distributed under the terms of the Creative Commons Attribution License permitting unrestricted use and redistribution provided that the original author and source are credited.',
                        'license' => 'CC-BY-4.0',
                    ],
                    'title' => 'Mitochondrial support of persistent presynaptic vesicle mobilization with age-dependent synaptic growth after LTP',
                    'authorLine' => 'Heather L Smith et al',
                    'versionDate' => '2016-12-19T00:00:00Z',
                    'researchOrganisms' => [
                        'Rat',
                    ],
                    'published' => '2016-12-19T00:00:00Z',
                    'sortDate' => '2016-12-19T00:00:00Z',
                    'statusDate' => '2016-12-19T00:00:00Z',
                    'pdf' => 'https://publishing-cdn.elifesciences.org/15276/elife-15276-v1.pdf',
                    'subjects' => [
                        [
                            'id' => 'neuroscience',
                            'name' => 'Neuroscience',
                        ],
                    ],
                    'elocationId' => 'e15276',
                    'id' => '15276',
                    'stage' => 'published',
                ];
                break;

        }
    }

    public function getJsonResponse()
    {
        /** @var Response $response */
        $response = $this->getResponse();
        if (!$response->isOk()) {
            $this->fail('Response returned was not 200: '.$response->getContent());
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

    public function addDocumentToElasticSearch($doc)
    {
        $obj = is_string($doc) ? json_decode($doc, true) : $doc;
        $this->client->indexJsonDocument($obj['type'], $obj['id'], is_string($doc) ? $doc : json_encode($doc), true);
    }

    public function addDocumentsToElasticSearch(array $docs)
    {
        foreach ($docs as $doc) {
            $this->addDocumentToElasticSearch($doc);
        }
    }

    public function createConfiguration()
    {
        if (file_exists(__DIR__.'/../../../../config/local.php')) {
            $this->isLocal = true;
            $config = include __DIR__.'/../../../../config/local.php';
        } else {
            $this->isLocal = false;
            $config = include __DIR__.'/../../../../config/ci.php';
        }

        $config['elastic_index'] = 'elife_test';

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
        $this->client->deleteIndex();
        $lines = $this->runCommand('search:setup');
        if ($lines[0] === 'No alive nodes found in your cluster') {
            $this->fail('Elasticsearch may not be installed, skipping');
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

        foreach (['debug', 'info', 'warning', 'critical', 'emergency', 'alert', 'log', 'notice', 'error'] as $level) {
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
