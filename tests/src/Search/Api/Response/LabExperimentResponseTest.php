<?php

namespace tests\eLife\Search\Api\Response;

use eLife\Search\Api\Response\LabsExperimentResponse;
use tests\eLife\Search\SerializerTest;

class LabExperimentResponseTest extends SerializerTest
{
    public function getResponseClass() : string
    {
        return LabsExperimentResponse::class;
    }

    public function jsonProvider() : array
    {
        $minimum = '
        {
            "number": 1,
            "title": "Experimental eLife Lens search page",
            "published": "2015-04-01T11:32:47+00:00",
            "image": {
                "alt": "",
                "sizes": {
                    "2:1": {
                        "900": "https://placehold.it/900x450",
                        "1800": "https://placehold.it/1800x900"
                    },
                    "16:9": {
                        "250": "https://placehold.it/250x141",
                        "500": "https://placehold.it/500x281"
                    },
                    "1:1": {
                        "70": "https://placehold.it/70x70",
                        "140": "https://placehold.it/140x140"
                    }
                }
            },
            "content": [
                {
                    "type": "paragraph",
                    "text": "We are going to use feedback and usage stats to determine whether we can bring any features from this kind of demo over to our main site."
                }
            ]
        }
        ';

        $minimum_expected = '
        {
            "number": 1,
            "type": "labs-experiment",
            "title": "Experimental eLife Lens search page",
            "published": "2015-04-01T11:32:47+00:00",
            "image": {
                "alt": "",
                "sizes": {
                    "2:1": {
                        "900": "https://placehold.it/900x450",
                        "1800": "https://placehold.it/1800x900"
                    },
                    "16:9": {
                        "250": "https://placehold.it/250x141",
                        "500": "https://placehold.it/500x281"
                    },
                    "1:1": {
                        "70": "https://placehold.it/70x70",
                        "140": "https://placehold.it/140x140"
                    }
                }
            }
        }
        ';

        $complete = '
        {
            "number": 1,
            "title": "Experimental eLife Lens search page",
            "impactStatement": "Today on eLife Labs we are launching a small demo of a search interface that brings together some elements of eLife Lens and some of the native power of a technology called elasticsearch. Head over to the demo to try it out now.",
            "published": "2015-04-01T11:32:47+00:00",
            "image": {
                "alt": "",
                "sizes": {
                    "2:1": {
                        "900": "https://placehold.it/900x450",
                        "1800": "https://placehold.it/1800x900"
                    },
                    "16:9": {
                        "250": "https://placehold.it/250x141",
                        "500": "https://placehold.it/500x281"
                    },
                    "1:1": {
                        "70": "https://placehold.it/70x70",
                        "140": "https://placehold.it/140x140"
                    }
                }
            }
        }
        ';
        $complete_expected = '
        {
            "number": 1,
            "type": "labs-experiment",
            "title": "Experimental eLife Lens search page",
            "impactStatement": "Today on eLife Labs we are launching a small demo of a search interface that brings together some elements of eLife Lens and some of the native power of a technology called elasticsearch. Head over to the demo to try it out now.",
            "published": "2015-04-01T11:32:47+00:00",
            "image": {
                "alt": "",
                "sizes": {
                    "2:1": {
                        "900": "https://placehold.it/900x450",
                        "1800": "https://placehold.it/1800x900"
                    },
                    "16:9": {
                        "250": "https://placehold.it/250x141",
                        "500": "https://placehold.it/500x281"
                    },
                    "1:1": {
                        "70": "https://placehold.it/70x70",
                        "140": "https://placehold.it/140x140"
                    }
                }
            }
        }
        ';

        return [
            [
                $minimum, $minimum_expected,
            ],
            [
                $complete, $complete_expected,
            ],
        ];
    }
}
