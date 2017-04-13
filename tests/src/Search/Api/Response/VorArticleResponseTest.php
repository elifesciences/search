<?php

namespace tests\eLife\Search\Api\Response;

use eLife\Search\Api\Response\ArticleResponse\VorArticle;
use tests\eLife\Search\RamlRequirement;
use tests\eLife\Search\SerializerTest;

class VorArticleResponseTest extends SerializerTest
{
    use RamlRequirement;

    public function getResponseClass() : string
    {
        return VorArticle::class;
    }

    public function jsonProvider() : array
    {
        return [
            [
                $this->getFixture('article-vor/v1/minimum.json'), '
                {
                    "status": "vor",
                    "statusDate": "2015-09-10T00:00:00Z",
                    "stage": "published",
                    "volume": 4,
                    "version": 1,
                    "elocationId": "e09560",
                    "doi": "10.7554\/eLife.09560",
                    "type": "research-article",
                    "published": "2015-09-10T00:00:00Z",
                    "id": "09560",
                    "title": "<i>Homo naledi<\/i>, a new species of the genus <i>Homo<\/i> from the Dinaledi Chamber, South Africa"
                }
                ',
            ],
            [
                $this->getFixture('article-vor/v1/complete.json'), '
                {
                    "status": "vor",
                    "statusDate": "2015-09-10T00:00:00Z",
                    "stage": "published",
                    "volume": 4,
                    "version": 1,
                    "issue": 3,
                    "titlePrefix": "Title prefix",
                    "elocationId": "e09560",
                    "doi": "10.7554\/eLife.09560",
                    "authorLine": "Lee R Berger et al",
                    "pdf": "https:\/\/elifesciences.org\/content\/4\/e09560.pdf",
                    "type": "research-article",
                    "published": "2015-09-10T00:00:00Z",
                    "subjects": [
                        {
                            "id": "genomics-evolutionary-biology",
                            "name": "Genomics and Evolutionary Biology"
                        }
                    ],
                    "image": {
                        "banner": {
                            "alt": "",
                            "uri": "https://iiif.elifesciences.org/lax:09560/elife-09560-fig1-v1.tif",
                            "source": {
                                "mediaType": "image/jpeg",
                                "uri": "https://iiif.elifesciences.org/lax:09560/elife-09560-fig1-v1.tif/full/full/0/default.jpg",
                                "filename": "an-image.jpg"
                            },
                            "size": {
                                "width": 4194,
                                "height": 4714
                            },
                            "focalPoint": {
                                "x": 25,
                                "y": 75
                            }
                        },
                        "thumbnail": {
                            "alt": "",
                            "uri": "https://iiif.elifesciences.org/lax:09560/elife-09560-fig1-v1.tif",
                            "source": {
                                "mediaType": "image/jpeg",
                                "uri": "https://iiif.elifesciences.org/lax:09560/elife-09560-fig1-v1.tif/full/full/0/default.jpg",
                                "filename": "an-image.jpg"
                            },
                            "size": {
                                "width": 4194,
                                "height": 4714
                            },
                            "focalPoint": {
                                "x": 25,
                                "y": 75
                            }
                        }
                    },
                    "id": "09560",
                    "title": "<i>Homo naledi<\/i>, a new species of the genus <i>Homo<\/i> from the Dinaledi Chamber, South Africa",
                    "impactStatement": "A new hominin species has been unearthed in the Dinaledi Chamber of the Rising Star cave system in the largest assemblage of a single species of hominins yet discovered in Africa."
                }',
            ],
        ];
    }
}
