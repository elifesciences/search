<?php

namespace eLife\Search\Api\Elasticsearch;

use DateTimeImmutable;
use eLife\Search\Api\Query\QueryBuilder;
use eLife\Search\Api\Query\QueryExecutor;

final class ElasticQueryBuilder implements QueryBuilder
{
    private $order;
    private $exec;

    const PHP_DATETIME_FORMAT = 'yyyy/MM/dd HH:mm:ss';
    const ELASTIC_DATETIME_FORMAT = 'Y/m/d H:i:s';

    const DATE_DEFAULT = 'sortDate';
    const DATE_PUBLISHED = 'published';

    private $dateType;

    public function __construct(string $index, ElasticQueryExecutor $exec)
    {
        $this->query['index'] = $index;
        $this->query['body']['aggregations']['type_agg']['terms'] = [
            'field' => '_type',
            'size' => 18,
        ];
        $this->query['body']['aggregations']['subject_agg'] = [
            'nested' => [
                'path' => 'subjects',
            ],
            'aggs' => [
                'name' => [
                    'terms' => [
                        'field' => 'subjects.id',
                        'size' => 15,
                        'min_doc_count' => 0,
                    ],
                    'aggs' => [
                        'name' => [
                            'terms' => [
                                'field' => 'subjects.name',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->exec = $exec;
    }

    private $query = [];

    private function sort($sort = [])
    {
        $this->query['body']['sort'] = $this->query['body']['sort'] ?? [];
        $this->query['body']['sort'][] = $sort;
    }

    private function getSort($reverse = false)
    {
        if ($reverse) {
            return $this->order === 'desc' ? 'asc' : 'desc';
        }

        return $this->order;
    }

    public function setDateType(string $field): QueryBuilder
    {
        // No need to do any fancy enum checks.
        if ($field === self::DATE_PUBLISHED) {
            $this->dateType = $field;

            return $this;
        }
        // Defaults to.. the default. (sortDate)
        $this->dateType = self::DATE_DEFAULT;

        return $this;
    }

    private function postQuery(string $key, $value)
    {
        /*
         "post_filter": {
                "query": {
                   "bool" : {
                        "must" : [
                            {"terms": {"subjects.id": ["cell-biology"]}},
                            {"terms": { "_type": ["research-article"]}}
                        ]
                    }
                }
            },
         */
        if (isset($this->query['body']['post_filter']['terms'])) {
            $firstFilter = $this->query['body']['post_filter']['terms'];
            $secondFilter = [];
            $secondFilter[$key] = $value;
            unset($this->query['body']['post_filter']['terms']);
            $this->query['body']['post_filter']['query']['bool']['must'] = [
                ['terms' => $firstFilter],
                ['terms' => $secondFilter],
            ];
        } elseif (isset($this->query['body']['post_filter']['query']['bool']['must'])) {
            $nthFilter = [];
            $nthFilter[$key] = $value;
            $this->query['body']['post_filter']['query']['bool']['must'][] = ['terms' => $nthFilter];
        } else {
            $this->query['body']['post_filter'] = $this->query['body']['post_filter'] ?? [];
            $this->query['body']['post_filter']['terms'][$key] = $value;
        }
    }

    private function query($key, array $body)
    {
        $this->query['body']['query'] = $this->query['body']['query'] ?? [];
        $this->query['body']['query'][$key] = $body;
    }

    private function setBoostings($string = '')
    {

        /* Boost results based on 'type' */
        $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 1, 'query' => ['match' => ['type' => 'collection']]]];
        $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 2, 'query' => ['match' => ['type' => 'podcast-episode']]]];
        $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 1, 'query' => ['match' => ['type' => 'interview']]]];
        $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 2, 'query' => ['match' => ['type' => 'correction']]]];
        $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 1, 'query' => ['match' => ['type' => 'insight']]]];
        $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 1, 'query' => ['match' => ['type' => 'feature']]]];
        $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 1, 'query' => ['match' => ['type' => 'labs-experiment']]]];
        $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 1, 'query' => ['match' => ['type' => 'editorial']]]];
        $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 2, 'query' => ['match' => ['type' => 'retraction']]]];
        $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 0, 'query' => ['match' => ['type' => 'blog-article']]]];
        $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 0, 'query' => ['match' => ['type' => 'research-advance']]]];
        $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 0, 'query' => ['match' => ['type' => 'research-article']]]];
        $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 0, 'query' => ['match' => ['type' => 'research-exchange']]]];
        $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 0, 'query' => ['match' => ['type' => 'registered-report']]]];
        $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 0, 'query' => ['match' => ['type' => 'replication-study']]]];
        $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 0, 'query' => ['match' => ['type' => 'short-report']]]];
        $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 0, 'query' => ['match' => ['type' => 'tools-resources']]]];

        if (!(empty($string))) {
            /* Boost results based on which field(s) match the query term */
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 9, 'query' => ['match' => ['title' => $string]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 10, 'query' => ['match' => ['authorLine' => $string]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 2, 'query' => ['match' => ['elocationId' => $string]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 2, 'query' => ['match' => ['doi' => $string]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 10, 'query' => ['match' => ['Person Author Name' => $string]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 10, 'query' => ['match' => ['Institution' => $string]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 10, 'query' => ['match' => ['Group Author' => $string]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 10, 'query' => ['match' => ['Author in group' => $string]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 10, 'query' => ['match' => ['On behalf of group' => $string]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 8, 'query' => ['match' => ['reviewers' => $string]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 2, 'query' => ['match' => ['orcid' => $string]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 4, 'query' => ['match' => ['subject' => $string]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 4, 'query' => ['match' => ['keywords' => $string]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 4, 'query' => ['match' => ['organism' => $string]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 3, 'query' => ['match' => ['impactStatement' => $string]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 3, 'query' => ['match' => ['abstract' => $string]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 3, 'query' => ['match' => ['digest' => $string]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 2, 'query' => ['match' => ['body' => $string]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 2, 'query' => ['match' => ['appendix' => $string]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 2, 'query' => ['match' => ['letters' => $string]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 8, 'query' => ['match' => ['dataset' => $string]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 8, 'query' => ['match' => ['curator' => $string]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 6, 'query' => ['match' => ['funding' => $string]]]];
        }
    }

    public function searchFor(string $string): QueryBuilder
    {
        if ($string !== '') {
            /* Query all fields for the actaul query term*/
            $this->query['body']['query']['bool']['must'][] = ['query' => ['match' => ['_all' => $string]]];
            $this->setBoostings($string);
        }
        
        return $this;
    }

    public function order(string $direction = 'desc'): QueryBuilder
    {
        $this->order = $direction === 'desc' ? 'desc' : 'asc';

        return $this;
    }

    public function paginate(int $page = 1, int $perPage = 10): QueryBuilder
    {
        $this->query['from'] = ($page - 1) * $perPage;
        $this->query['size'] = $perPage;

        return $this;
    }

    public function sortByRelevance($reverse = false): QueryBuilder
    {
        $this->sort([
            '_score' => [
                'order' => $this->getSort($reverse),
            ],
        ]);

        return $this;
    }

    public function sortByDate($reverse = false): QueryBuilder
    {
        $this->sort($this->dateQuery([
            'order' => $this->getSort($reverse),
            'missing' => '_last',
        ]));

        return $this;
    }

    public function whereSubjects(array $subjects = []): QueryBuilder
    {
        $this->postQuery('subjects.id', $subjects);

        return $this;
    }

    public function whereType(array $types = []): QueryBuilder
    {
        $this->postQuery('_type', $types);

        return $this;
    }

    public function getRawQuery(): array
    {
        return $this->query;
    }

    public function getQuery(): QueryExecutor
    {
        $exec = clone $this->exec;
        $exec->setQuery($this);

        return $exec;
    }

    private function dateQuery($query)
    {
        $arr = [];
        $arr[$this->dateType] = $query;

        return $arr;
    }

    public function betweenDates(DateTimeImmutable $startDate = null, DateTimeImmutable $endDate = null): QueryBuilder
    {
        $query = ['format' => self::PHP_DATETIME_FORMAT];
        if ($startDate) {
            $query['gte'] = $startDate->format(self::ELASTIC_DATETIME_FORMAT);
        }
        if ($endDate) {
            $query['lte'] = $endDate->format(self::ELASTIC_DATETIME_FORMAT);
        }

        $this->query['body']['query']['bool']['must'][] = ['query' => ['range' => $this->dateQuery($query)]];

        return $this;
    }
}
