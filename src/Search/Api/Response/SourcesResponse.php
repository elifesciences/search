<?php
/**
 * Created by PhpStorm.
 * User: Stephen
 * Date: 29/09/2016
 * Time: 16:23.
 */

namespace eLife\Search\Api\Response;

use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Type;

class SourcesResponse
{
    /**
     * @Type("string")
     * @SerializedName("mediaType")
     */
    public $mediaType;

    /**
     * @Type("string")
     */
    public $uri;
}
