<?php

namespace eLife\Search\Api\Response\Common;

use JMS\Serializer\Annotation\Since;
use JMS\Serializer\Annotation\Type;

abstract class NamedResponse
{
    /**
     * @Type(eLife\Search\Api\Response\ImageThumbnailResponse::class)
     * @Since(version="1")
     */
    public $image;

    /**
     * @Type("string")
     * @Since(version="1")
     */
    public $id;

    /**
     * @Type("string")
     * @Since(version="1")
     */
    public $type;

    /**
     * @Type("array<string, string>")
     */
    public $name;
}
