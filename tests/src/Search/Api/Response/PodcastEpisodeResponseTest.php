<?php

namespace tests\eLife\Search\Api\Response;

use eLife\Search\Api\Response\PodcastEpisodeResponse;
use tests\eLife\Search\RamlRequirement;
use tests\eLife\Search\SerializerTest;

class PodcastEpisodeResponseTest extends SerializerTest
{
    use RamlRequirement;

    public function getResponseClass() : string
    {
        return PodcastEpisodeResponse::class;
    }

    public function jsonProvider() : array
    {
        return [
            [
                $this->getFixture('podcast-episode/v1/minimum.json'), '
                {
                    "number": 30,
                    "type": "podcast-episode",
                    "title": "June 2016",
                    "published": "2016-07-01T08:30:15+00:00",
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
                    "sources": [
                        {
                            "mediaType": "audio/mpeg",
                            "uri": "https://nakeddiscovery.com/scripts/mp3s/audio/eLife_Podcast_16.06.mp3"
                        }
                    ]
                }',
            ],
            [
                $this->getFixture('podcast-episode/v1/complete.json'),
                '
                {
                    "number": 30,
                    "sources": [
                        {
                            "mediaType": "audio\/mpeg",
                            "uri": "https:\/\/nakeddiscovery.com\/scripts\/mp3s\/audio\/eLife_Podcast_16.06.mp3"
                        }
                    ],
                    "type": "podcast-episode",
                    "title": "July 2016",
                    "impactStatement": "In this episode of the eLife podcast we hear about drug production, early career researchers, honeybees, human migrations and pain.",
                    "subjects": [
                        {
                            "id": "biochemistry",
                            "name": "Biochemistry"
                        },
                        {
                            "id": "ecology",
                            "name": "Ecology"
                        }
                    ],
                    "published": "2016-07-01T08:30:15+00:00",
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
                    }
                }'
            ]
        ];
    }
}
