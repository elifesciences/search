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
}
