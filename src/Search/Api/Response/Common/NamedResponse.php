<?php

namespace eLife\Search\Api\Response\Common;

use JMS\Serializer\Annotation\Accessor;
use JMS\Serializer\Annotation\Since;
use JMS\Serializer\Annotation\Type;

abstract class NamedResponse
{
    /**
     * @Type(eLife\Search\Api\Response\SingleImageResponse::class)
     * @Since(version="1")
     * @Accessor(getter="getHttpsImage")
     */
    public $image;

    public function getHttpsImage()
    {
        return $this->image ? $this->image->https() : null;
    }

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
