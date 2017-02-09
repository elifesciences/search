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
                        'min_doc_count' => 0
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
                ['terms' => $secondFilter]
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

    private function must($query)
    {
        $this->query['body']['query']['bool']['must'][] = $query;
    }

    public function searchFor(string $string): QueryBuilder
    {
        if ($string !== '') {
            $this->query('match', ['_all' => $string]);
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
        $this->sort([
            'sortDate' => [
                'order' => $this->getSort($reverse),
                'missing' => '_last',
            ],
        ]);

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

    public function betweenDates(DateTimeImmutable $fromDate = null, DateTimeImmutable $toDate = null): QueryBuilder
    {
        $query = ['format' => self::PHP_DATETIME_FORMAT];
        if ($fromDate) {
            $query['gte'] = $fromDate->format(self::ELASTIC_DATETIME_FORMAT);
        }
        if ($toDate) {
            $query['lte'] = $toDate->format(self::ELASTIC_DATETIME_FORMAT);
        }
        $this->query('range', [
            'sortDate' => $query,
        ]);

        return $this;
    }
}
