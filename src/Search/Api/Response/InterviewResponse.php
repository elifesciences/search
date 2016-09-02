<?php

namespace eLife\Search\Api\Response;

use eLife\Search\Api\Response\Common\Published;
use eLife\Search\Api\Response\Common\SnippetFields;
use JMS\Serializer\Annotation\Accessor;
use JMS\Serializer\Annotation\Since;
use JMS\Serializer\Annotation\Type;

class InterviewResponse implements SearchResult
{
    use SnippetFields;
    use Published;

    /**
     * @Type("string")
     * @Since(version="1")
     * @Accessor(getter="getType")
     */
    public $type = 'interview';

    /*
     * @todo
     */
    // public $interviewee;
}
