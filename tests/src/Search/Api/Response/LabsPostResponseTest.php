<?php

namespace tests\eLife\Search\Api\Response;

use eLife\Search\Api\Response\LabsPostResponse;
use tests\eLife\Search\RamlRequirement;
use tests\eLife\Search\SerializerTest;

class LabsPostResponseTest extends SerializerTest
{
    use RamlRequirement;

    public function getResponseClass() : string
    {
        return LabsPostResponse::class;
    }

    public function jsonProvider() : array
    {
        return [
            [
                $this->getFixture('labs-post/v1/minimum.json'),
                '
                {
                    "id": "80000001",
                    "type": "labs-post",
                    "title": "Experimental eLife Lens search page",
                    "published": "2015-04-01T11:32:47Z",
                    "image": {
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
                            }
                        }
                    }
                }',
            ],
            [
                $this->getFixture('labs-post/v1/complete.json'),
                '
                {
                    "id": "80000001",
                    "type": "labs-post",
                    "title": "Experimental eLife Lens search page",
                    "impactStatement": "Today on eLife Labs we are launching a small demo of a search interface that brings together some elements of eLife Lens and some of the native power of a technology called elasticsearch. Head over to the demo to try it out now.",
                    "published": "2015-04-01T11:32:47Z",
                    "image": {
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
                    }
                }',
            ],
        ];
    }
}
