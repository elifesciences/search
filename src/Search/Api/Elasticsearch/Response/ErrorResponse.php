<?php

namespace eLife\Search\Api\Elasticsearch\Response;

use JMS\Serializer\Annotation\Type;

class ErrorResponse implements NonContentResponse
{
    /**
     * @Type("array")
     */
    public $error;

    public function getType() : string
    {
        return 'error';
    }
}
