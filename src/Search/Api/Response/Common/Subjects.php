<?php

namespace eLife\Search\Api\Response\Common;

use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\Since;
use JMS\Serializer\Annotation\Type;

trait Subjects
{
    /**
     * @Type("array<string>")
     * @Since(version="1")
     * @Groups({"snippet", "full"})
     */
    public $subjects;
}
