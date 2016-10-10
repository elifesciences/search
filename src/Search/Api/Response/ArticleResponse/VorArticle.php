<?php

namespace eLife\Search\Api\Response\ArticleResponse;

use eLife\Search\Api\Response\ArticleResponse;
use JMS\Serializer\Annotation\Since;
use JMS\Serializer\Annotation\Type;

final class VorArticle implements ArticleResponse
{
    use Article;

    /**
     * @Type("string")
     * @Since(version="1")
     */
    public $status = 'vor';
}
