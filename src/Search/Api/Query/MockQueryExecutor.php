<?php

namespace eLife\Search\Api\Query;

final class MockQueryExecutor implements QueryExecutor
{
    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function getHash() : string
    {
        return md5(json_encode($this->data));
    }

    public function execute() : QueryResponse
    {
        return new MockQueryResponse(array_map(function ($item) {
            return json_encode($item, true);
        }, $this->data));
    }
}
