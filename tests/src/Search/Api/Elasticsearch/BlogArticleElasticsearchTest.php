<?php

namespace tests\eLife\Search\Api\Elasticsearch;

final class BlogArticleElasticsearchTest extends ElasticsearchTestCase
{
    public function jsonProvider() : array
    {
        return [
            [
                '{
                    "id": "12456",
                    "type": "blog-article",
                    "title": "some blog article",
                    "impactStatement": "Something impacting in a statement like fashion.",
                    "published": "2016-06-09T15:15:10+00:00"
                }',
            ],
        ];
    }
}
