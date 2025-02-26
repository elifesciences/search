<?php

namespace src\Search\Web;

use PHPUnit\Framework\Attributes\Group;
use tests\eLife\Search\Web\ElasticTestCase;

#[Group('web')]
class ElifeAssessmentTermsTest extends ElasticTestCase
{
    public function testGivenOnlyOneOfTwoPapersIsLandmarkWhenFilteringForLandmarkSignificanceItOnlyReturnsTheLandmarkPaper()
    {
        $significance = 'landmark';
        $this->addDocumentsToElasticSearch([
            $this->provideArbitraryArticleWithoutElifeAssessment(),
            $this->provideArticleWithElifeAssessmentSignificance($significance)
        ]);
        $response = $this->performApiRequest(['elifeAssessmentSignificance' => [$significance]]);
        $this->assertEquals(1, $response['total']);
        $this->assertResultsOnlyContainFilteredSignificance($significance, $response['items']);
    }

    public function testGivenTwoPapersOneLandmarkAndOneImportantWhenFilteringForLandmarkOrImportantSignificanceItReturnsBothPapers()
    {
        $this->addDocumentsToElasticSearch([
            $this->provideArticleWithElifeAssessmentSignificance('landmark'),
            $this->provideArticleWithElifeAssessmentSignificance('important'),
        ]);
        $response = $this->performApiRequest(['elifeAssessmentSignificance' => ['landmark', 'important']]);
        $this->assertEquals(2, $response['total']);
    }

    private function assertResultsOnlyContainFilteredSignificance(string $significance, array $items)
    {
        foreach ($items as $item) {
            $this->assertItemContainsElifeAssessmentWithSpecificSignificanceProperty($significance, $item);
        }
    }

    private function assertItemContainsElifeAssessmentWithSpecificSignificanceProperty(string $significance, array $item)
    {
        $this->assertArrayHasKey('elifeAssessment', $item);
        $this->assertArrayHasKey('significance', $item['elifeAssessment']);
        $this->assertEquals([$significance], $item['elifeAssessment']['significance']);
    }

    private function performApiRequest(array $queryStringParameters)
    {
        $this->newClient();
        $this->jsonRequest('GET', '/search', $queryStringParameters);
        return $this->getJsonResponseAsAssociativeArray();
    }

    private function provideArbitraryArticleWithoutElifeAssessment()
    {
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
    }

    private function provideArticleWithElifeAssessmentSignificance(string $significance)
    {
        return array_merge(
            $this->provideArbitraryArticleWithoutElifeAssessment(),
            [
                'id' => (string) rand(1, 99999),
                'elifeAssessment' => [
                    'significance' => [$significance],
                ],
            ],
        );
    }
}
