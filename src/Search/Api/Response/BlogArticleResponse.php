<?php

namespace eLife\Search\Api\Response;

use eLife\Search\Api\Response\Common\SnippetFields;
use eLife\Search\Api\Response\Common\Subjects;
use JMS\Serializer\Annotation\Accessor;
use JMS\Serializer\Annotation\ReadOnly;
use JMS\Serializer\Annotation\Since;
use JMS\Serializer\Annotation\Type;

class BlogArticleResponse implements SearchResult
{
    use SnippetFields;
    use Subjects;

    /**
     * @Type("DateTime<'Y-m-d\TH:i:sP'>")
     * @Since(version="1")
     */
    public $published;

    /**
     * @Type("string")
     * @Since(version="1")
     * @Accessor(getter="getType")
     * @ReadOnly
     */
    public $type = 'blog-article';
}
