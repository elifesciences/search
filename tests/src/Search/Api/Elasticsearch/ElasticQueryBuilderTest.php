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
     * @test
     * @dataProvider reservedCharsProvider
     */
    public function escapesReservedChars(string $search, string $expectedQuery)
    {
        $this->queryBuilder->searchFor($search);

        $query = $this->queryBuilder->getRawQuery();

        $this->assertEquals($expectedQuery.'~', $query['body']['query']['bool']['must'][0]['query_string']['query']);
    }

    public function reservedCharsProvider() : array
    {
        return [
            [
                '<>',
                '',
            ],
            [
                '()',
                '\(\)',
            ],
            [
                '/',
                '\/',
            ],
            [
                ':',
                '\:',
            ],
            [
                'http://europepmc.org/article/MED/8777778~',
                'http\:\/\/europepmc.org\/article\/MED\/8777778\~',
            ],
            [
                'http://europepmc.org/article/MED/8777778',
                'http\:\/\/europepmc.org\/article\/MED\/8777778',
            ],
            [
                'europepmc.org/article/MED/8777778',
                'europepmc.org\/article\/MED\/8777778',
            ],
            [
                'rheumatoid arthritis (RA)',
                'rheumatoid arthritis \(RA\)',
            ],
        ];
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
