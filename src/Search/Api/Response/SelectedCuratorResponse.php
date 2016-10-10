<?php

namespace eLife\Search\Api\Response;

use eLife\Search\Api\Response\Common\NamedResponse;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Since;
use JMS\Serializer\Annotation\Type;

final class SelectedCuratorResponse extends NamedResponse
{
    /**
     * @Type("boolean")
     * @Since(version="1")
     * @SerializedName("etAl")
     */
    public $etAl = false;
}
