<?php

namespace tests\eLife\Search\Api\Response;

use eLife\Search\Api\Response\BlogArticleResponse;
use tests\eLife\Search\SerializerTest;

class BlogArticleResponseTest extends SerializerTest
{
    /**
     * @test
     */
    public function testDeserialization()
    {
        $article = $this->responseFromArray(BlogArticleResponse::class, [
            'id' => '12456',
            'title' => 'some blog article',
            'impactStatement' => 'Something impacting in a statement like fashion.',
            'published' => '2016-06-09T15:15:10+00:00',
        ]);

        $this->assertEquals('blog-article', $article->type);
        $this->assertEquals('12456', $article->id);
        $this->assertEquals('some blog article', $article->title);
        $this->assertEquals('Something impacting in a statement like fashion.', $article->impactStatement);
        $this->assertEquals('2016-06-09T15:15:10+00:00', $article->published->format('c'));
    }

    public function getResponseClass() : string
    {
        return BlogArticleResponse::class;
    }

    public function jsonProvider() : array
    {
        $actual = '
            {
                "id": "12456",
                "impactStatement": "Something impacting in a statement like fashion.",
                "title": "some blog article",
                "published": "2016-06-09T15:15:10+00:00"
            }
        ';
        $expected = '
            {
                "id": "12456",
                "type": "blog-article",
                "impactStatement": "Something impacting in a statement like fashion.",
                "title": "some blog article",
                "published": "2016-06-09T15:15:10+00:00"
            }
        ';

        return [
            [
                $actual, $expected,
            ],
        ];
    }
}
