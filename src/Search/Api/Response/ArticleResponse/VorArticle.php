<?php

namespace eLife\Search\Api\Response\ArticleResponse;

use eLife\Search\Api\Response\ArticleResponse;
use JMS\Serializer\Annotation\Since;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\VirtualProperty;

class VorArticle implements ArticleResponse
{
    /**
     * @Type("string")
     * @Since(version="1")
     */
    public $status = 'vor';

    /**
     * @VirtualProperty("type")
     * @Type("string")
     * @Since(version="1")
     */
    public function getType() : string
    {
        return 'editorial';
    }
}
