<?php

namespace eLife\Search\Api\Response;

use eLife\Search\Api\Response\Common\Image;
use eLife\Search\Api\Response\Common\Published;
use eLife\Search\Api\Response\Common\SnippetFields;
use JMS\Serializer\Annotation\Accessor;
use JMS\Serializer\Annotation\Since;
use JMS\Serializer\Annotation\Type;

final class InterviewResponse implements SearchResult
{
    use SnippetFields;
    use Image;
    use Published;

    /**
     * @Type("string")
     * @Since(version="1")
     * @Accessor(getter="getType")
     */
    public $type = 'interview';

    /**
     * @Type(PersonResponse::class)
     */
    public $interviewee;
}
