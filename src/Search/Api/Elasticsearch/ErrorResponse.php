<?php

namespace eLife\Search\Api\Elasticsearch;

use JMS\Serializer\Annotation as Serializer;

class ErrorResponse implements NonContentResponse
{
    /**
     * @Serializer\Type("array")
     */
    public $error;

    public function getType() : string
    {
        return 'error';
    }
}
