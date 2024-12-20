<?php

namespace eLife\Search\Api\Elasticsearch\Response;

use JMS\Serializer\Annotation\Type;

final class SuccessResponse implements NonContentResponse
{
    /**
     * @Type("boolean")
     */
    public bool $acknowledged;

    /**
     * @Type("boolean")
     */
    public bool $found;

    /**
     * @Type("boolean")
     */
    public bool $inserted;
}
