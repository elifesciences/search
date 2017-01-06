<?php

namespace eLife\Search\Api\Elasticsearch;

use eLife\Search\Api\Query\QueryBuilder;
use eLife\Search\Api\Query\QueryExecutor;

final class ElasticQueryBuilder implements QueryBuilder
{
    private $order;
    private $exec;

    public function __construct(string $index, ElasticQueryExecutor $exec)
    {
        $this->query['index'] = $index;
        $this->query['body']['aggregations']['type_agg']['terms'] = [
            'field' => '_type',
        ];
        $this->query['body']['aggregations']['subject_agg'] = [
            'nested' => [
                'path' => 'subjects',
            ],
            'aggs' => [
                'name' => [
                    'terms' => [
                        'field' => 'subjects.id',
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
        $this->query['sort'] = $this->query['sort'] ?? [];
        $this->query['sort'][] = $sort;
    }

    private function getSort($reverse = false)
    {
        if ($reverse) {
            return $this->order === 'desc' ? 'asc' : 'desc';
        }

        return $this->order;
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
        $this->sort("_score:{$this->getSort($reverse)}");

        return $this;
    }

    public function sortByDate($reverse = false): QueryBuilder
    {
        $this->sort("statusDate:{$this->getSort($reverse)}");
        $this->sort("published:{$this->getSort($reverse)}");
        $this->sort("updated:{$this->getSort($reverse)}");

        return $this;
    }

    public function whereSubjects(array $subjects = []): QueryBuilder
    {
        $this->must([
            'terms' => ['subjects.id' => $subjects],
        ]);

        return $this;
    }

    public function whereType(array $types = []): QueryBuilder
    {
        $this->must([
            'terms' => ['_type' => $types],
        ]);

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
}
