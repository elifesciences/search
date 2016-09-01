<?php

namespace eLife\Search\Api\Response;

use eLife\Search\Api\Response\Common\SnippetFields;
use eLife\Search\Api\Response\Common\Subjects;
use JMS\Serializer\Annotation\Accessor;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\Since;
use JMS\Serializer\Annotation\Type;

class BlogArticleResponse implements SearchResult
{
    use SnippetFields;
    use Subjects;

    /**
     * @Type("DateTime<'c'>")
     * @Since(version="1")
     * @Groups({"snippet", "full"})
     */
    public $published;

    /**
     * @Type("eLife\Search\Api\Response\Block")
     * @Since(version="1")
     * @Groups({"full"})
     */
    public $content;

    /**
     * @Type("string")
     * @Since(version="1")
     * @Groups({"snippet"})
     * @Accessor(getter="getType")
     */
    public $type = 'blog-article';
}
