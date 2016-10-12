<?php

namespace eLife\Search\Api\Elasticsearch\Response;

use JMS\Serializer\Annotation\Type;

class SuccessResponse implements NonContentResponse
{
    /**
     * @Type("boolean")
     */
    public $acknowledged;

    /**
     * @Type("boolean")
     */
    public $found;

    /**
     * @Type("boolean")
     */
    public $inserted;
}
