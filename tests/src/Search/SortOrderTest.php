<?php

namespace tests\eLife\Search;

/**
 * @group web
 */
class SortOrderTest extends ElasticTestCase
{
    /**
     * @test
     */
    public function testDateOrder()
    {
        $this->addDocumentsToElasticSearch([
            [
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
            ],
            [
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
            ],
        ]);

        $this->newClient();
        $this->jsonRequest('GET', '/search', ['order' => 'asc', 'sort' => 'date']);
        $response = $this->getJsonResponse();
        $this->assertEquals($response->items[0]->id, '19662');
        $this->assertEquals($response->items[1]->id, '15275');

        $this->newClient();
        $this->jsonRequest('GET', '/search', ['order' => 'desc', 'sort' => 'date']);
        $response = $this->getJsonResponse();
        $this->assertEquals($response->items[0]->id, '15275');
        $this->assertEquals($response->items[1]->id, '19662');
    }
}
