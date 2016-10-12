<?php

namespace eLife\Search\Api\Elasticsearch;

class SuccessResponse implements NonContentResponse
{
    public function getType() : string
    {
        return 'success';
    }
}
