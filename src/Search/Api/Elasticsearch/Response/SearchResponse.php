<?php

namespace eLife\Search\Api\Elasticsearch\Response;

use eLife\Search\Api\Elasticsearch\ResponsePartials\HitItem;
use eLife\Search\Api\Elasticsearch\ResponsePartials\ResponseHits;
use eLife\Search\Api\Query\QueryResponse;

final class SearchResponse implements ElasticResponse, QueryResponse
{
    use ResponseHits;

    private $_results;
    private $cursor = 0;

    public function getResults() : array
    {
        if (null !== $this->_results) {
            return $this->_results;
        }

        return $this->_results = array_map(function (HitItem $i) {
            return $i->unwrap();
        }, $this->getHits()->getHitItem());
    }

    public function current(): mixed
    {
        $results = $this->getResults();

        return $results[$this->cursor];
    }

    public function next(): void
    {
        ++$this->cursor;
    }

    public function key(): mixed
    {
        return $this->cursor;
    }

    public function valid(): bool
    {
        $results = $this->getResults();

        return isset($results[$this->cursor]);
    }

    public function rewind(): void
    {
        $this->cursor = 0;
    }

    public function getTypeTotals() : array
    {
        if (isset($this->aggregations['type_agg']['buckets'])) {
            $types = [];
            foreach ($this->aggregations['type_agg']['buckets'] as $bucket) {
                $types[$bucket['key']] = $bucket['doc_count'];
            }

            return $types;
        }

        return [];
    }

    public function getSubjects() : array
    {
        if (isset($this->aggregations['subject_agg']['name']['buckets'])) {
            $types = [];
            foreach ($this->aggregations['subject_agg']['name']['buckets'] as $bucket) {
                $types[] = [
                    'id' => $bucket['key'],
                    'name' => $bucket['name']['buckets'][0]['key'] ?? null,
                    'results' => $bucket['doc_count'],
                ];
            }

            return $types;
        }

        return [];
    }

    public function toArray() : array
    {
        return $this->getResults();
    }

    public function map(callable $fn) : array
    {
        return array_map($fn, $this->getResults());
    }
}
