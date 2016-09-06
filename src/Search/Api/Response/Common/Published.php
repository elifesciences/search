<?php

namespace eLife\Search\Api\Response\Common;

use JMS\Serializer\Annotation\Since;
use JMS\Serializer\Annotation\Type;

trait Published
{
    /**
     * @Type("DateTime<'Y-m-d\TH:i:sP'>")
     * @Since(version="1")
     */
    public $published;
}
