<?php

namespace tests\eLife\Search\Api\Response;

use eLife\Search\Api\Response\BlogArticleResponse;
use tests\eLife\Search\RamlRequirement;
use tests\eLife\Search\SerializerTest;

class BlogArticleResponseTest extends SerializerTest
{
    use RamlRequirement;

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

        return [
            [
                $this->getFixture('blog-article/v1/minimum.json'), '
                {
                    "id": "2",
                    "type": "blog-article",
                    "title": "More eLife authors are linking submissions to their ORCID iDs",
                    "published": "2016-06-09T15:15:10+00:00",
                    "impactStatement": "eLife sees positive results of requiring corresponding authors to register and link their profiles to their ORCID iDs"
                }'
            ],
            [
                $this->getFixture('blog-article/v1/complete.json'), '
                {
                    "published": "2016-07-08T08:33:25+00:00",
                    "type": "blog-article",
                    "id": "1",
                    "title": "Media coverage: Slime can see",
                    "impactStatement": "In their research paper \u2013 Cyanobacteria use micro-optics to sense light direction \u2013 Schuergers et al. reveal how bacterial cells act as the equivalent of a microscopic eyeball or the world\u2019s oldest and smallest camera eye, allowing them to \u2018see\u2019.",
                    "subjects": [
                        {
                            "id": "biophysics-structural-biology",
                            "name": "Biophysics and Structural Biology"
                        }
                    ]
                }
                '
            ]
        ];
    }
}
