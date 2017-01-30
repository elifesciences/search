<?php

namespace tests\eLife\Search;

/**
 * @group web2
 */
class FacetCountTest extends ElasticTestCase
{
    /**
     * @test
     */
    public function test_facet_count_is_accurate_after_filtering_subject()
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
            ],
            [
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
            ],
        ]);

        $this->newClient();
        $this->jsonRequest('GET', '/search', ['order' => 'asc', 'sort' => 'date', 'subject' => ['cell-biology']]);
        $response = $this->getJsonResponse();
        $subjects = array_filter($response->subjects, function ($subject) {
            return $subject->id === 'cell-biology';
        });
        // The amount of research articles are still accurate.
        $this->assertEquals($response->types->{'research-article'}, 3, 'The amount of research article are still accurate');
        // The number of cell-biology is equal to the doc count
        $this->assertEquals(current($subjects)->results, 1, 'Only one result is returned for the subject');
        // The number of items in the results should be filtered.
        $this->assertEquals(count($response->items), 1, 'Only one result is in the body');
    }
}
