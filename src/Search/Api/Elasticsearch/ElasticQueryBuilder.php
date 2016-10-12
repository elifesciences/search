<?php

namespace eLife\Search\Api\Elasticsearch;

use eLife\Search\Api\Query\QueryBuilder;
use eLife\Search\Api\Query\QueryExecutor;
use LogicException;

final class ElasticQueryBuilder implements QueryBuilder
{
    private $order;
    private $exec;
    private $run = false;

    public function __construct(string $index, ElasticQueryExecutor $exec)
    {
        $this->query['index'] = $index;
        $this->exec = $exec;
    }

    private $query = [];

    private function sort($sort)
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

    public function searchFor(string $string) : QueryBuilder
    {
        if ($string === '') {
            $this->sort('_doc');
        } else {
            $this->query('match', ['_all' => $string]);
        }

        return $this;
    }

    public function order(string $direction = 'desc') : QueryBuilder
    {
        $this->order = $direction === 'desc' ? 'desc' : 'asc';

        return $this;
    }

    public function paginate(int $page = 1, int $perPage = 10) : QueryBuilder
    {
        $this->query['from'] = ($page - 1) * $perPage;
        $this->query['size'] = $perPage;

        return $this;
    }

    public function sortByRelevance($reverse = false) : QueryBuilder
    {
        $this->sort(['_all' => $this->getSort($reverse)]);

        return $this;
    }

    public function sortByDate($reverse = false) : QueryBuilder
    {
        $this->sort(['published' => $this->getSort($reverse)]);

        return $this;
    }

    public function whereSubjects(array $subjects = []) : QueryBuilder
    {
        $this->query('terms', [
            'subjects' => $subjects,
        ]);

        return $this;
    }

    public function whereType(array $types = []) : QueryBuilder
    {
        $this->query('terms', [
            'type' => $types,
        ]);

        return $this;
    }

    public function getRawQuery() : array
    {
        return $this->query;
    }

    public function getQuery() : QueryExecutor
    {
//        if ($this->run) {
//            throw new LogicException('You cannot run the same query twice.');
//        }
        $exec = clone $this->exec;
        $exec->setQuery($this);
//        $this->run = true;

        return $exec;
    }
}
