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

    public function testGivenTwoPapersOneLandmarkAndOneWithoutElifAssessmentWhenFilteringForNotApplicableAndLandmarkSignificanceItReturnsBothPapers()
    {
        $this->markTestIncomplete();
    }

    public function testGivenFourPapersTwoOfWhichLackSignificanceWhenFilteringForNotAssignedSignificanceItReturnsThePapersWithNoAssignedSignificance()
    {
        $articleWithEmptySignificanceArray = $this->provideArticleWithElifeAssessmentWithAnEmptySignificanceArray();
        $articleWithNoSignificanceKey = $this->provideArticleWithElifeAssessmentWithoutSignificanceKey();
        $this->addDocumentsToElasticSearch([
            $this->provideArticleWithElifeAssessmentSignificance('landmark'), // assigned significance
            $this->provideArbitraryArticleWithoutElifeAssessment(), // not applicable
            $articleWithEmptySignificanceArray, // this is one of the two not-assigned cases
            $articleWithNoSignificanceKey, // this is the other case of the two not-assigned cases
        ]);
        $response = $this->performApiRequest(['elifeAssessmentSignificance' => ['not-assigned']]);
        $idsOfReturnedArticles = $this->toItemIds($response['items']);
        $this->assertContains($articleWithNoSignificanceKey['id'], $idsOfReturnedArticles, 'Expected article with no significance key to be returned');
        $this->assertContains($articleWithEmptySignificanceArray['id'], $idsOfReturnedArticles, 'Expected article with empty significance array to be returned');
        $this->assertEquals(2, $response['total']);
    }

    public function testGivenOnlyOneOfTwoPapersIsExceptionalWhenFilteringForExceptionalStrengthItOnlyReturnsTheExceptionalPaper()
    {
        $strength = 'exceptional';
        $this->addDocumentsToElasticSearch([
            $this->provideArbitraryArticleWithoutElifeAssessment(),
            $this->provideArticleWithElifeAssessmentStrength($strength)
        ]);
        $response = $this->performApiRequest(['elifeAssessmentStrength' => [$strength]]);

        $this->assertEquals(1, $response['total']);
        $this->assertResultsOnlyContainFilteredStrength($strength, $response['items']);
    }

    public function testGivenTwoPapersOneExceptionalAndOneCompellingWhenFilteringForExceptionalOrCompellingStrengthItReturnsBothPapers()
    {
        $this->addDocumentsToElasticSearch([
            $this->provideArticleWithElifeAssessmentStrength('exceptional'),
            $this->provideArticleWithElifeAssessmentStrength('compelling'),
        ]);
        $response = $this->performApiRequest(['elifeAssessmentStrength' => ['exceptional', 'compelling']]);
        $this->assertEquals(2, $response['total']);
    }

    private function toItemIds(array $items) : array
    {
        $ids = [];
        foreach ($items as $item) {
            $ids[] = $item['id'];
        }
        return $ids;
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

    private function assertResultsOnlyContainFilteredStrength(string $strength, array $items)
    {
        foreach ($items as $item) {
            $this->assertItemContainsElifeAssessmentWithSpecificStrengthProperty($strength, $item);
        }
    }

    private function assertItemContainsElifeAssessmentWithSpecificStrengthProperty(string $strength, array $item)
    {
        $this->assertArrayHasKey('elifeAssessment', $item);
        $this->assertArrayHasKey('strength', $item['elifeAssessment']);
        $this->assertEquals([$strength], $item['elifeAssessment']['strength']);
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
                    'title' => 'eLife assessment',
                    'content' => [
                        [
                            'type' => 'paragraph',
                            'text' => 'lorem ipsum',
                        ],
                    ],
                    'significance' => [$significance],
                ],
            ],
        );
    }

    private function provideArticleWithElifeAssessmentStrength(string $strength)
    {
        return array_merge(
            $this->provideArbitraryArticleWithoutElifeAssessment(),
            [
                'id' => (string) rand(1, 99999),
                'elifeAssessment' => [
                    'title' => 'eLife assessment',
                    'content' => [
                        [
                            'type' => 'paragraph',
                            'text' => 'lorem ipsum',
                        ],
                    ],
                    'strength' => [$strength],
                ],
            ],
        );
    }

    private function provideArticleWithElifeAssessmentWithoutSignificanceKey()
    {
        return array_merge(
            $this->provideArbitraryArticleWithoutElifeAssessment(),
            [
                'id' => (string) rand(1, 99999),
                'elifeAssessment' => [
                    'title' => 'eLife assessment',
                    'content' => [
                        [
                            'type' => 'paragraph',
                            'text' => 'lorem ipsum',
                        ],
                    ],
                ],
            ],
        );
    }

    private function provideArticleWithElifeAssessmentWithAnEmptySignificanceArray()
    {
        return array_merge(
            $this->provideArbitraryArticleWithoutElifeAssessment(),
            [
                'id' => (string) rand(1, 99999),
                'elifeAssessment' => [
                    'title' => 'eLife assessment',
                    'content' => [
                        [
                            'type' => 'paragraph',
                            'text' => 'lorem ipsum',
                        ],
                    ],
                    'significance' => [],
                ],
            ],
        );
    }
}
