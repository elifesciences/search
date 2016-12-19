<?php

namespace eLife\Search\Api\Response;

use eLife\Search\Api\Response\Common\SnippetFields;
use JMS\Serializer\Annotation\Accessor;
use JMS\Serializer\Annotation\ReadOnly;
use JMS\Serializer\Annotation\Since;
use JMS\Serializer\Annotation\Type;

final class EventResponse implements SearchResult
{
    use SnippetFields;

    /**
     * @see https://github.com/schmittjoh/JMSSerializerBundle/issues/459
     * @Type("DateTime<'Y-m-d\TH:i:s\Z'>")
     * @Since(version="1")
     */
    public $starts;

    /**
     * @see https://github.com/schmittjoh/JMSSerializerBundle/issues/459
     * @Type("DateTime<'Y-m-d\TH:i:s\Z'>")
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
     * @ReadOnly
     */
    public $type = 'event';
}
