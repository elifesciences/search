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
        $this->markTestIncomplete();
        /** @phpstan-ignore deadCode.unreachable */
        $this->assertEquals(1, $response->total);
        $this->assertResultsOnlyContainFilteredSignificance($significance, $results);
    }

    /** @phpstan-ignore method.unused */
    private function assertResultsOnlyContainFilteredSignificance(string $significance, array $results)
    {
        foreach ($results as $item) {
            /** @phpstan-ignore method.notFound */
            $this->assertItemContainsElifeAssessment($item);
        }
    }

    private function performApiRequest(array $queryStringParameters)
    {
        $this->newClient();
        $this->jsonRequest('GET', '/search', $queryStringParameters);
        return $this->getJsonResponse();
    }

    private function provideArbitraryArticleWithoutElifeAssessment()
    {
        return [];
    }

    private function provideArticleWithElifeAssessmentSignificance(string $significance)
    {
        return [];
    }
}
