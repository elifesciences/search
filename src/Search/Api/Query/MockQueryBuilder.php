<?php

namespace eLife\Search\Api\Query;

final class MockQueryBuilder implements QueryBuilder
{
    const NAME = 'can\'t believe its not google';
    const VERSION = '1.0.0';
    private $clever = false;

    private $data;

    public function __construct(array $data = null, $clever = false)
    {
        $this->data = $data === null ? json_decode(file_get_contents(__DIR__.'/data/search.json'), true) : $data;
        $this->clever = $clever;
    }

    public function searchFor(string $string) : QueryBuilder
    {
        if (!$this->clever) {
            return new static(
                $this->data = array_filter($this->data, function ($item) use ($string) {
                    return strpos(json_encode($item), $string) !== false;
                }),
                $this->clever
            );
        }

        return new static(
            $this->data = array_filter($this->data, function ($item) use ($string) {
                $searchable = array_merge([
                    'title' => '',
                    'impactStatement' => '',
                    'abstract' => '',
                    'keywords' => '',
                    'body' => [],
                    'researchOrganisms' => [],
                    'decisionLetter' => [],
                    'chapters' => [],
                ], $item);

                $searchable_key = json_encode([
                    $searchable['title'],
                    $searchable['impactStatement'],
                    $searchable['abstract'],
                    $searchable['keywords'],
                    $searchable['body'],
                    $searchable['chapters'],
                ]);

                return strpos($searchable_key, $string) !== false;
            }),
            $this->clever
        );
    }

    public function order(string $direction = 'desc') : QueryBuilder
    {
        if ($direction === 'asc') {
            return new static(
                $this->data = array_reverse($this->data),
                $this->clever
            );
        }

        return $this;
    }

    public function paginate(int $page = 1, int $perPage = 10) : QueryBuilder
    {
        return new static(
            $this->data = array_splice($this->data, ($page - 1) * $perPage, $perPage),
            $this->clever
        );
    }

    public function sortByRelevance($reverse = false) : QueryBuilder
    {
        // TODO: Implement sortByRelevance() method.
    }

    public function sortByDate($reverse = false) : QueryBuilder
    {
        // TODO: Implement sortByDate() method.
    }

    public function whereSubjects(array $subjects = []) : QueryBuilder
    {
        return new static(
            $this->data = array_filter($this->data, function ($item) use ($subjects) {
                $check = $item['subjects'] ?? [];

                return !array_diff($subjects, $check);
            }),
            $this->clever
        );
    }

    public function whereType(array $types = []) : QueryBuilder
    {
        return new static(
            $this->data = array_filter($this->data, function ($item) use ($types) {
                $check = $item['type'] ?? [];

                return !array_diff($types, $check);
            }),
            $this->clever
        );
    }

    public function getQuery() : QueryExecutor
    {
        return new class($this->data) implements QueryExecutor {
            public $data;

            public function __construct($data)
            {
                $this->data = $data;
            }

            public function execute() : array
            {
                return $this->data;
            }
        };
    }
}
