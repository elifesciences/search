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
        if ($this->_results !== null) {
            return $this->_results;
        }

        return $this->_results = array_map(function (HitItem $i) {
            return $i->unwrap();
        }, $this->getHits()->getHitItem());
    }

    public function current()
    {
        $results = $this->getResults();

        return $results[$this->cursor];
    }

    public function next()
    {
        ++$this->cursor;
    }

    public function key()
    {
        return $this->cursor;
    }

    public function valid()
    {
        $results = $this->getResults();

        return isset($results[$this->cursor]);
    }

    public function rewind()
    {
        $this->cursor = 0;
    }

    public function serialize()
    {
        // TODO: Implement serialize() method.
    }

    public function unserialize($serialized)
    {
        // TODO: Implement unserialize() method.
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
