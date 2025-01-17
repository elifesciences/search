<?php

namespace tests\eLife\Search\Web;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('web')]
class ExampleWebTest extends ElasticTestCase
{
    #[Test]
    public function testCanRunCommand()
    {
        $logs = $this->runCommand('help');
        $this->assertEquals(0, count($logs));
    }

    #[Test]
    public function testElasticSearchIndex()
    {
        $this->addDocumentToElasticSearch([
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
        ]);

        $this->newClient();
        $this->jsonRequest('GET', '/search', [
            'for' => 'Extracellular actin',
            'per-page' => 1,
            'page' => 1,
        ]);
        $json = $this->getJsonResponse();

        $this->assertEquals($json->total, 1);
        $this->assertEquals($json->items[0]->status, 'vor');
        $this->assertEquals($json->items[0]->id, '19662');
    }
}
