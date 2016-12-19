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
                    "volume": 4,
                    "version": 1,
                    "elocationId": "e09560",
                    "doi": "10.7554\/eLife.09560",
                    "authorLine": "Lee R Berger et al",
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
                            "sizes": {
                                "2:1": {
                                    "900": "https:\/\/placehold.it\/900x450",
                                    "1800": "https:\/\/placehold.it\/1800x900"
                                }
                            }
                        },
                        "thumbnail": {
                            "alt": "",
                            "sizes": {
                                "16:9": {
                                    "250": "https:\/\/placehold.it\/250x141",
                                    "500": "https:\/\/placehold.it\/500x281"
                                },
                                "1:1": {
                                    "70": "https:\/\/placehold.it\/70x70",
                                    "140": "https:\/\/placehold.it\/140x140"
                                }
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
