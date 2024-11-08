<?php

namespace tests\eLife\Search\Web;

use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use eLife\Search\Api\Elasticsearch\PlainElasticsearchClient;
use eLife\Search\Console;
use eLife\Search\IndexMetadata;
use eLife\Search\Kernel;
use eLife\Search\KeyValueStore\ElasticSearchKeyValueStore;
use Psr\Log\NullLogger;
use RuntimeException;
use Symfony\Component\BrowserKit\Client;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

abstract class ElasticTestCase extends WebTestCase
{
    protected $console;
    /** @var MappedElasticsearchClient */
    protected $client;

    public function getCollectionFixture(int $num)
    {
        switch ($num) {
            default:
            case 0:
                return json_decode('
                {
                      "updated": "2017-02-08T10:32:46Z",
                      "sortDate": "2017-02-08T10:32:46Z",
                      "published": "2014-02-08T10:32:46Z",
                      "selectedCurator": {
                        "image": {
                          "alt": "",
                          "uri": "https://iiif.elifesciences.org/lax/09560%2Felife-09560-fig1-v1.tif",
                          "source": {
                            "mediaType": "image/jpeg",
                            "uri": "https://iiif.elifesciences.org/lax/09560%2Felife-09560-fig1-v1.tif/full/full/0/default.jpg",
                            "filename": "an-image.jpg"
                          },
                          "size": {
                            "width": 4194,
                            "height": 4714
                          }
                        },
                        "id": "da020f46",
                        "type": {
                            "id": "senior-editor",
                            "label": "Senior Editor"
                        },
                        "name": {
                          "preferred": "Prabhat Jha",
                          "index": "Jha, Prabhat"
                        },
                        "etAl": true
                      },
                      "type": "collection",
                      "id": "3a8bbf09",
                      "title": "Tropical disease: A selection of papers",
                      "impactStatement": "eLife has published papers on many tropical diseases, including malaria, Ebola, leishmaniases, Dengue and African sleeping sickness. The articles below have been selected by eLife editors to give a flavour of the breadth of research on tropical diseases published by the journal.\n\nMore articles can be found on our subject pages for\u00a0<a href=\"http:\/\/elifesciences.org\/category\/epidemiology-and-global-health\">Epidemiology and Global Health<\/a>\u00a0and\u00a0<a href=\"http:\/\/elifesciences.org\/category\/microbiology-and-infectious-disease\">Microbiology and Infectious Disease<\/a>.",
                      "image": {
                        "banner": {
                          "alt": "",
                          "uri": "https://iiif.elifesciences.org/lax/09560%2Felife-09560-fig1-v1.tif",
                          "source": {
                            "mediaType": "image/jpeg",
                            "uri": "https://iiif.elifesciences.org/lax/09560%2Felife-09560-fig1-v1.tif/full/full/0/default.jpg",
                            "filename": "an-image.jpg"
                          },
                          "size": {
                            "width": 4194,
                            "height": 4714
                          }
                        },
                        "thumbnail": {
                          "alt": "",
                          "uri": "https://iiif.elifesciences.org/lax/09560%2Felife-09560-fig1-v1.tif",
                          "source": {
                            "mediaType": "image/jpeg",
                            "uri": "https://iiif.elifesciences.org/lax/09560%2Felife-09560-fig1-v1.tif/full/full/0/default.jpg",
                            "filename": "an-image.jpg"
                          },
                          "size": {
                            "width": 4194,
                            "height": 4714
                          }
                        }
                      }
                    }
                ', true);
        }
    }

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
                    'title' => 'BUZZWORD ONLY FOUND HERE Mitochondrial support of persistent presynaptic vesicle mobilization with age-dependent synaptic growth after LTP',
                    'authorLine' => 'Heather L Smith et al.',
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
                    'authorLine' => 'Naren Srinivasan et al.',
                    'versionDate' => '2016-12-19T12:31:04Z',
                    'researchOrganisms' => [
                        'D. melanogaster',
                    ],
                    'version' => 3,
                    'published' => '2016-11-18T00:00:00Z',
                    'sortDate' => '2016-12-05T16:36:45Z',
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
                    'authorLine' => 'Robert L Smith et al.',
                    'versionDate' => '2016-12-19T00:00:00Z',
                    'researchOrganisms' => [
                        'Rat',
                    ],
                    'published' => '2016-12-19T12:42:00Z',
                    'sortDate' => '2016-12-19T12:42:00Z',
                    'statusDate' => '2016-12-19T12:42:00Z',
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
            case 3:
                return [
                    'status' => 'poa',
                    'volume' => 6,
                    'doi' => '10.7554/eLife.15278',
                    'type' => 'correction',
                    'version' => 2,
                    'copyright' => [
                        'holder' => 'Smith et al',
                        'statement' => 'This article is distributed under the terms of the Creative Commons Attribution License permitting unrestricted use and redistribution provided that the original author and source are credited.',
                        'license' => 'CC-BY-4.0',
                    ],
                    'title' => 'Mitochondrial support of persistent presynaptic vesicle mobilization with age-dependent synaptic growth after LTP',
                    'authorLine' => 'Robert L Smith et al.',
                    'versionDate' => '2016-12-19T00:00:00Z',
                    'researchOrganisms' => [
                        'Rat',
                    ],
                    'published' => '2017-01-19T00:00:00Z',
                    'sortDate' => '2017-01-19T00:00:00Z',
                    'statusDate' => '2017-01-19T00:00:00Z',
                    'pdf' => 'https://publishing-cdn.elifesciences.org/15276/elife-15276-v1.pdf',
                    'subjects' => [
                        [
                            'id' => 'neuroscience',
                            'name' => 'Neuroscience',
                        ],
                        [
                            'id' => 'immunology',
                            'name' => 'Immunology',
                        ],
                    ],
                    'elocationId' => 'e15278',
                    'id' => '15278',
                    'stage' => 'published',
                ];
                break;
            case 4:
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
                    'title' => 'ONLY FOUND HERE Mitochondrial support of persistent presynaptic vesicle mobilization with age-dependent synaptic growth after LTP',
                    'authorLine' => 'BUZZWORD Heather L Smith et al.',
                    'impactStatement' => 'Impact Robert L Smith statement.',
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
                    'id' => '15279',
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
            $decodedResponse = json_decode($response->getContent(), true);
            if (!$decodedResponse) {
                $decodedResponse = $response->getContent();
            }
            $this->fail('Response returned was not 200 but '.$response->getStatusCode().': '.var_export($decodedResponse, true));
        }
        $json = json_decode($response->getContent());

        return $json;
    }


    public function addDocumentToElasticSearch($doc)
    {
        $obj = is_string($doc) ? json_decode($doc, true) : $doc;
        $doc['snippet'] = [
            'format' => 'json',
            'value' => json_encode($doc),
        ];
        $this->mappedClient->indexJsonDocument($obj['id'], is_string($doc) ? $doc : json_encode($doc), true);
    }

    public function addDocumentsToElasticSearch(array $docs)
    {
        foreach ($docs as $doc) {
            $this->addDocumentToElasticSearch($doc);
        }
    }

    protected function mapHeaders($headers)
    {
        $httpHeaders = [];
        foreach ($headers as $key => $header) {
            $httpHeaders['HTTP_'.$key] = $headers[$key];
        }

        return $httpHeaders;
    }

    protected function jsonRequest(string $verb, string $endpoint, array $params = [], array $headers = [])
    {
        $server = array_merge([
            'HTTP_ACCEPT' => 'application/vnd.elife.search+json; version=1',
            'CONTENT_TYPE' => 'application/vnd.elife.search+json; version=1',
        ], $this->mapHeaders($headers));

        return $this->api->request(
            $verb,
            $endpoint,
            $params,
            [],
            $server
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

    public function setUp(): void
    {
        parent::setUp();
        $this->kernel = new Kernel($this->createConfiguration());
        $this->kernel->updateIndexMetadata(IndexMetadata::fromContents(
            'elife_test',
            'elife_test'
        ));

        $this->plainClient = new PlainElasticsearchClient(
            $this->kernel->get('elastic.elasticsearch.plain'),
            $indexName ?? ElasticSearchKeyValueStore::INDEX_NAME
        );
        $this->mappedClient = $this->kernel->get('elastic.client.read');

        $this->plainClient->deleteIndex('elife_test');
        $lines = $this->runCommand('search:setup');
        if (!$lines) {
            $this->fail('Could not run search:setup, try running it manually');
        }
        if ('No alive nodes found in your cluster' === $lines[0]) {
            $this->fail('Elasticsearch may not be installed, skipping');
        }
        $this->assertStringStartsWith('Created new empty index', $lines[0], 'Failed to run set up of the test (index creation)');
    }

    public function tearDown(): void
    {
        $this->plainClient->deleteIndex('elife_test');
        parent::tearDown();
    }

    public function runCommand(string $command)
    {
        $logs = [];
        $log = $this->returnCallback(function ($message) use (&$logs) {
            $logs[] = $message;
        });
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
        $application = new Console($app, $this->kernel, $logger, $this->kernel->get('config'));

        $fp = tmpfile();
        $input = new StringInput($command);
        $output = new StreamOutput($fp);

        $application->run($input, $output);

        return $logs;
    }
}
