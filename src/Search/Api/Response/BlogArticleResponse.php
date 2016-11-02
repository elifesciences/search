<?php

namespace eLife\Search\Api\Response;

use eLife\Search\Api\Response\Common\Image;
use eLife\Search\Api\Response\Common\SnippetFields;
use eLife\Search\Api\Response\Common\Subjects;
use JMS\Serializer\Annotation\Accessor;
use JMS\Serializer\Annotation\ReadOnly;
use JMS\Serializer\Annotation\Since;
use JMS\Serializer\Annotation\Type;

final class BlogArticleResponse implements SearchResult
{
    use SnippetFields;
    use Subjects;
    use Image;

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
