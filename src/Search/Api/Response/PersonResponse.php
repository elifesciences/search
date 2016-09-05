<?php

namespace eLife\Search\Api\Response;

use eLife\Search\Api\Response\Common\Image;
use JMS\Serializer\Annotation\Since;
use JMS\Serializer\Annotation\Type;

class PersonResponse
{
    use Image;

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
