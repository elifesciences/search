<?php

namespace test\eLife\Search\Api\Elasticsearch;

use eLife\Search\Api\Elasticsearch\ElasticQueryBuilder;

class ElasticQueryBuilderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ElasticQueryBuilder */
    private $queryBuilder;

    public function setUp()
    {
        $this->queryBuilder = new ElasticQueryBuilder('foo');
    }

    /**
     * @test
     *
     */
    public function appliesWordLimit()
    {
        $this->assertEquals(
            'search under limit',
            $this->queryBuilder->applyWordLimit('search under limit', $wordsOverLimit)
        );
        $this->assertEquals(0, $wordsOverLimit);

        $searchUptoLimit = $this->createSearchString(32);

        $this->assertEquals(
            $this->createSearchString(32),
            $this->queryBuilder->applyWordLimit($searchUptoLimit, $wordsOverLimit)
        );
        $this->assertEquals(0, $wordsOverLimit);

        $searchExceedingLimit = $this->createSearchString(37);

        $this->assertEquals(
            $this->createSearchString(32),
            $this->queryBuilder->applyWordLimit($searchExceedingLimit, $wordsOverLimit)
        );
        $this->assertEquals(5, $wordsOverLimit);
    }

    /**
     * Create a dummy search query x words in length.
     */
    private function createSearchString(int $wordLength) : string
    {
        return implode(' ', array_map(function ($k) {
            return 'word'.($k+1);
        }, array_keys(array_fill(0, $wordLength, null))));
    }
}
