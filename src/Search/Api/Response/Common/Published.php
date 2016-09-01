<?php
/**
 * Created by PhpStorm.
 * User: Stephen
 * Date: 01/09/16
 * Time: 16:46.
 */

namespace eLife\Search\Api\Response\Common;

use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\Since;
use JMS\Serializer\Annotation\Type;

trait Published
{
    /**
     * @Type("DateTime<'c'>")
     * @Since(version="1")
     * @Groups({"snippet", "full"})
     */
    public $published;
}
