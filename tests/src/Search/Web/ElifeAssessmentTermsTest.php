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
        $landmarkPaper = $this->provideArticleWithElifeAssessmentSignificance($significance);
        $this->addDocumentsToElasticSearch([
            $this->provideArbitraryArticleWithoutElifeAssessment(),
            $landmarkPaper
        ]);
        $response = $this->performApiRequest(['elifeAssessmentSignificance' => [$significance]]);
        $idsOfReturnedArticles = $this->toItemIds($response['items']);
        $this->assertContains($landmarkPaper['id'], $idsOfReturnedArticles, 'Expected article landmark significance to be returned');
        $this->assertEquals(1, $response['total']);
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

    public function testGivenTwoPapersOneLandmarkAndOneWithoutElifeAssessmentWhenFilteringForNotApplicableAndLandmarkSignificanceItReturnsBothPapers()
    {
        $this->addDocumentsToElasticSearch([
            $this->provideArticleWithElifeAssessmentSignificance('landmark'),
            $this->provideArbitraryArticleWithoutElifeAssessment(),
        ]);
        $response = $this->performApiRequest(['elifeAssessmentSignificance' => ['landmark', 'not-applicable']]);
        $this->assertEquals(2, $response['total']);
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
        $exceptionalPaper = $this->provideArticleWithElifeAssessmentStrength($strength);
        $this->addDocumentsToElasticSearch([
            $this->provideArbitraryArticleWithoutElifeAssessment(),
            $exceptionalPaper
        ]);
        $response = $this->performApiRequest(['elifeAssessmentStrength' => [$strength]]);
        $idsOfReturnedArticles = $this->toItemIds($response['items']);
        $this->assertContains($exceptionalPaper['id'], $idsOfReturnedArticles, 'Expected article exceptional strength to be returned');
        $this->assertEquals(1, $response['total']);
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

    public function testGivenTwoPapersOneExceptionalAndOneWithoutElifeAssessmentWhenFilteringForNotApplicableAndExceptionalStrengthItReturnsBothPapers()
    {
        $this->addDocumentsToElasticSearch([
            $this->provideArticleWithElifeAssessmentStrength('exceptional'),
            $this->provideArbitraryArticleWithoutElifeAssessment(),
        ]);
        $response = $this->performApiRequest(['elifeAssessmentStrength' => ['exceptional', 'not-applicable']]);
        $this->assertEquals(2, $response['total']);
    }
    
    public function testGivenFourPapersWithOnlyOneWithLandmarkAndExceptionalWhenFilteringForLandmarkAndExceptionalItReturnsOnlyThatPaper()
    {
        $landmarkAndExceptionalPaper =  $this->provideArticleWithElifeAssessment('landmark', 'exceptional');
        $this->addDocumentsToElasticSearch([
            $landmarkAndExceptionalPaper,
            $this->provideArbitraryArticleWithoutElifeAssessment(),
            $this->provideArticleWithElifeAssessment('landmark', 'compelling'),
            $this->provideArticleWithElifeAssessment('important', 'exceptional'),
        ]);
        $response = $this->performApiRequest(['elifeAssessmentSignificance' => ['landmark'], 'elifeAssessmentStrength' => ['exceptional']]);
        $idsOfReturnedArticles = $this->toItemIds($response['items']);
        $this->assertContains($landmarkAndExceptionalPaper['id'], $idsOfReturnedArticles, 'Expected article with landmark and exceptional terms');
        $this->assertEquals(1, $response['total']);
    }

    private function toItemIds(array $items) : array
    {
        $ids = [];
        foreach ($items as $item) {
            $ids[] = $item['id'];
        }
        return $ids;
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
    
    private function provideArticleWithElifeAssessment(string $significance, string $strength)
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
