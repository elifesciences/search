<?php

namespace tests\eLife\Search\Api\Elasticsearch;

use tests\eLife\Search\RamlRequirement;

final class BlogArticleElasticsearchTest extends ElasticsearchTestCase
{
    use RamlRequirement;

    public function jsonProvider() : array
    {
        return [
            [$this->getFixtureWithType('blog-article/v1/minimum.json', 'blog-article')],
            [$this->getFixtureWithType('blog-article/v1/complete.json', 'blog-article')],
        ];
    }
}
