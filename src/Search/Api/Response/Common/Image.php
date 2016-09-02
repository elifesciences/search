<?php

namespace eLife\Search\Api\Response\Common;

use JMS\Serializer\Annotation\Since;
use JMS\Serializer\Annotation\Type;

trait Image
{
    /**
     * @Type(eLife\Search\Api\Response\ImageResponse::class)
     * @Since(version="1")
     */
    public $image;
}
