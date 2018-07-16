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
                $this->getFixture('podcast-episode/v1/minimum.json'),
                '
                {
                    "number": 30,
                    "type": "podcast-episode",
                    "title": "June 2016",
                    "published": "2016-07-01T08:30:15Z",
                    "image": {
                                "banner": {
                                    "alt": "",
                                    "uri": "https://iiif.elifesciences.org/lax/09560%2Felife-09560-fig1-v1.tif",
                                    "source": {
                                        "mediaType": "image/jpeg",
                                        "uri": "https://iiif.elifesciences.org/lax/09560%2Felife-09560-fig1-v1.tif/full/full/0/default.jpg",
                                        "filename": "an-image.jpg"
                                    },
                                    "size": {
                                        "width": 4194,
                                        "height": 4714
                                    }
                                },
                                "thumbnail": {
                                    "alt": "",
                                    "uri": "https://iiif.elifesciences.org/lax/09560%2Felife-09560-fig1-v1.tif",
                                    "source": {
                                        "mediaType": "image/jpeg",
                                        "uri": "https://iiif.elifesciences.org/lax/09560%2Felife-09560-fig1-v1.tif/full/full/0/default.jpg",
                                        "filename": "an-image.jpg"
                                    },
                                    "size": {
                                        "width": 4194,
                                        "height": 4714
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
                    "published": "2016-07-01T08:30:15Z",
                    "image": {
                        "banner": {
                            "alt": "",
                            "uri": "https://iiif.elifesciences.org/lax/09560%2Felife-09560-fig1-v1.tif",
                            "source": {
                                "mediaType": "image/jpeg",
                                "uri": "https://iiif.elifesciences.org/lax/09560%2Felife-09560-fig1-v1.tif/full/full/0/default.jpg",
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
                            "uri": "https://iiif.elifesciences.org/lax/09560%2Felife-09560-fig1-v1.tif",
                            "source": {
                                "mediaType": "image/jpeg",
                                "uri": "https://iiif.elifesciences.org/lax/09560%2Felife-09560-fig1-v1.tif/full/full/0/default.jpg",
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
                    }
                }',
            ],
        ];
    }
}
