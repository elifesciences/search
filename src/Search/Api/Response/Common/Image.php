<?php

namespace eLife\Search\Api\Response\Common;


use JMS\Serializer\Annotation\Type;

trait Image
{
    /**
     * @Type(eLife\Search\Api\Response\ImageResponse)
     */
    public $image;
}
