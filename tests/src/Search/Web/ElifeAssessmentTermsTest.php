<?php

namespace src\Search\Web;

use PHPUnit\Framework\Attributes\Group;
use tests\eLife\Search\Web\ElasticTestCase;

#[Group('web')]
class ElifeAssessmentTermsTest extends ElasticTestCase
{
    public function testSignificanceFilteringWorks()
    {
        $this->markTestIncomplete();
        /** @phpstan-ignore deadCode.unreachable */
        $this->assertEquals(1, $total);
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
}
