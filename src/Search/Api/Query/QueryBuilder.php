<?php

namespace eLife\Search\Api\Query;

interface QueryBuilder
{
    public function searchFor(string $string) : QueryBuilder;

    public function order(string $direction = 'desc') : QueryBuilder;

    public function paginate(int $page = 1, int $perPage = 10) : QueryBuilder;

    public function sortByRelevance($reverse = false) : QueryBuilder;

    public function sortByDate($reverse = false) : QueryBuilder;

    public function whereSubjects(array $subjects = []) : QueryBuilder;

    public function whereType(array $types = []) : QueryBuilder;

    public function getQuery() : QueryExecutor;

    public function getRawQuery() : array;
}
