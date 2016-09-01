<?php

namespace eLife\Search\Api\Response;

use eLife\Search\Api\Response\Common\Image;
use eLife\Search\Api\Response\Common\Published;
use eLife\Search\Api\Response\Common\SnippetFields;
use JMS\Serializer\Annotation\Accessor;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\Since;
use JMS\Serializer\Annotation\Type;

class ExperimentResponse implements SearchResult
{
    use SnippetFields;
    use Image;
    use Published;

    /**
     * @Type("integer")
     * @Since(version="1")
     * @Groups({"full", "snippet"})
     */
    public $number;

    /**
     * @Type("string")
     * @Since(version="1")
     * @Groups({"snippet"})
     * @Accessor(getter="getType")
     */
    public $type = 'experiment';
}
