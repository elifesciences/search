<?php

namespace eLife\Search\Api\Elasticsearch\ResponsePartials;

use JMS\Serializer\Annotation\Accessor;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Type;

final class HitItem
{
    /**
     * @Type("array")
     * @SerializedName("_source")
     * @Accessor(setter="setSource")
     */
    public $_source;

    public function setSource(array $_source)
    {
        $this->_source = $_source['snippet']['value'];
    }

    public function unwrap() : array
    {
        return json_decode($this->_source, true);
    }
}
