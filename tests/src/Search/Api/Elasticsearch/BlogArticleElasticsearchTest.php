<?php

namespace tests\eLife\Search\Api\Elasticsearch;

use tests\eLife\Search\RamlRequirement;

final class BlogArticleElasticsearchTest extends ElasticsearchTestCase
{
    use RamlRequirement;

    public static function jsonProvider() : array
    {
        return [
            [static::getFixtureWithType('blog-article/v1/minimum.json', 'blog-article')],
            [static::getFixtureWithType('blog-article/v1/complete.json', 'blog-article')],
        ];
    }
}
