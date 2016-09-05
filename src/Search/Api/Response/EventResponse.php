<?php

namespace eLife\Search\Api\Response;

use eLife\Search\Api\Response\Common\SnippetFields;
use JMS\Serializer\Annotation\Accessor;
use JMS\Serializer\Annotation\Since;
use JMS\Serializer\Annotation\Type;

class EventResponse implements SearchResult
{
    use SnippetFields;

    /**
     * @Type("DateTime<'c'>")
     * @Since(version="1")
     */
    public $starts;

    /**
     * @Type("DateTime<'c'>")
     * @Since(version="1")
     */
    public $ends;

    /**
     * @Type("string")
     * @Since(version="1")
     */
    public $timezone;

    /**
     * @Type("string")
     * @Since(version="1")
     * @Accessor(getter="getType")
     */
    public $type = 'event';
}
