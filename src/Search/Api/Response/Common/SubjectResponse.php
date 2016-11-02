<?php

namespace eLife\Search\Api\Response\Common;

use JMS\Serializer\Annotation\Since;
use JMS\Serializer\Annotation\Type;

final class SubjectResponse
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
    public $name;
}
