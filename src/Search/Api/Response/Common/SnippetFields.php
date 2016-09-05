<?php

namespace eLife\Search\Api\Response\Common;

use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Since;
use JMS\Serializer\Annotation\Type;
use LogicException;

trait SnippetFields
{
    /**
     * @Type("string")
     * @Since(version="1")
     */
    public $id;

    /**
     * @Type("string")
     * @Since(version="1")
     */
    public $title;

    /**
     * @Type("string")
     * @Since(version="1")
     * @SerializedName("impactStatement")
     */
    public $impactStatement;

    public function getType() : string
    {
        if (!isset($this->type)) {
            throw new LogicException('Missing `type` property on class: '.__CLASS__);
        }

        return $this->type;
    }
}
