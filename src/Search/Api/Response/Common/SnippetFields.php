<?php

namespace eLife\Search\Api\Response\Common;

use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\Since;
use JMS\Serializer\Annotation\Type;
use LogicException;

trait SnippetFields
{
    /**
     * @Type("string")
     * @Since(version="1")
     * @Groups({"snippet", "full"})
     */
    public $id;

    /**
     * @Type("string")
     * @Since(version="1")
     * @Groups({"snippet", "full"})
     */
    public $title;

    /**
     * @Type("string")
     * @Since(version="1")
     * @Groups({"snippet", "full"})
     */
    public $impactStatement;

    /**
     * @Type("array<string>")
     * @Since(version="1")
     * @Groups({"snippet", "full"})
     */
    public $subjects;

    public function getType() : string
    {
        if (!isset($this->type)) {
            throw new LogicException('Missing `type` property on class: ' . __CLASS__);
        }
        return $this->type;
    }
}
