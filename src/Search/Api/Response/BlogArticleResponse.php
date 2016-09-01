<?php

namespace eLife\Search\Api\Response;


class BlogArticleResponse implements SearchResult
{

    public function getType() : string
    {
        return 'blog-article';
    }
}
