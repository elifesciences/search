<?php

namespace tests\eLife\Search\Api\Elasticsearch;

use eLife\ApiSdk\Client\BlogArticles;
use tests\eLife\Search\RamlRequirement;

final class BlogArticleElasticsearchTest extends ElasticsearchTestCase
{
    use RamlRequirement;

    public static function jsonProvider() : array
    {
        return [
            [static::getFixtureWithType('blog-article/v'.BlogArticles::VERSION_BLOG_ARTICLE.'/minimum.json', 'blog-article')],
            [static::getFixtureWithType('blog-article/v'.BlogArticles::VERSION_BLOG_ARTICLE.'/complete.json', 'blog-article')],
        ];
    }
}
