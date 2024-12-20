<?php

namespace eLife\Search\Api\Elasticsearch\Response;

use JMS\Serializer\Annotation\Type;

final class ErrorResponse implements NonContentResponse
{
    /**
     * @Type("array")
     * @var array<string, mixed> $error
     */
    public array $error;

    public function getType() : string
    {
        return 'error';
    }
}
