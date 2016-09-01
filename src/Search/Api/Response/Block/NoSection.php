<?php

namespace eLife\Search\Api\Response\Block;

use eLife\Search\Api\Response\Block;
use JMS\Serializer\Annotation\Since;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\VirtualProperty;

class NoSection implements Block
{
    /**
     * @Type("string")
     * @Since(version="1")
     */
    public $title;

    /**
     * @Type("array<eLife\Search\Api\Response\Block>")
     * @Since(version="1")
     */
    public $content;

    /**
     * @Type("string")
     * @Since(version="1")
     */
    public $type = 'section';

    /**
     * @VirtualProperty()
     */
    public function getType() : string
    {
        return $this->type;
    }
}
