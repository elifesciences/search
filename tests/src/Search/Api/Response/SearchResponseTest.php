<?php

namespace tests\eLife\Search\Api\Response;

use eLife\Search\Api\Response\SearchResponse;
use tests\eLife\Search\SerializerTest;

class SearchResponseTest extends SerializerTest
{
    public function getResponseClass() : string
    {
        return SearchResponse::class;
    }

    public function jsonProvider() : array
    {
        $minimum = '
        {
            "items": [
                {
                    "type": "research-article",
                    "status": "poa",
                    "id": "14107",
                    "version": 1,
                    "doi": "10.7554/eLife.14107",
                    "title": "Molecular basis for multimerization in the activation of the epidermal growth factor",
                    "published": "2016-03-28T00:00:00Z",
                    "volume": 5,
                    "elocationId": "e14107"
                },
                {
                    "type": "research-article",
                    "status": "vor",
                    "id": "09560",
                    "version": 1,
                    "doi": "10.7554/eLife.09560",
                    "title": "<i>Homo naledi</i>, a new species of the genus <i>Homo</i> from the Dinaledi Chamber, South Africa",
                    "published": "2015-09-10T00:00:00Z",
                    "volume": 4,
                    "elocationId": "e09560",
                    "pdf": "https://elifesciences.org/content/4/e09560.pdf",
                    "subjects": [
                        "genomics-evolutionary-biology"
                    ],
                    "impactStatement": "A new hominin species has been unearthed in the Dinaledi Chamber of the Rising Star cave system in the largest assemblage of a single species of hominins yet discovered in Africa.",
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
                },
                {
                    "type": "blog-article",
                    "id": "1",
                    "title": "Media coverage: Slime can see",
                    "impactStatement": "In their research paper – Cyanobacteria use micro-optics to sense light direction – Schuergers et al. reveal how bacterial cells act as the equivalent of a microscopic eyeball or the world’s oldest and smallest camera eye, allowing them to ‘see’.",
                    "published": "2016-07-08T08:33:25+00:00",
                    "subjects": [
                        "biophysics-structural-biology"
                    ]
                },
                {
                    "type": "labs-experiment",
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
                    }
                },
                {
                    "type": "podcast-episode",
                    "number": 29,
                    "title": "April/May 2016",
                    "published": "2016-05-27T13:19:42+00:00",
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
                    "sources": [
                        {
                            "mediaType": "audio/mpeg",
                            "uri": "https://nakeddiscovery.com/scripts/mp3s/audio/eLife_Podcast_16.05.mp3"
                        }
                    ]
                },
                {
                    "type": "event",
                    "id": "1",
                    "title": "Changing peer review in cancer research: a seminar at Fred Hutch",
                    "impactStatement": "How eLife is influencing the culture of peer review",
                    "starts": "2016-04-22T20:00:00+00:00",
                    "ends": "2016-04-22T21:00:00+00:00",
                    "timezone": "America/Seattle"
                },
                {
                    "type": "collection",
                    "id": "1",
                    "title": "Tropical disease",
                    "updated": "2015-09-16T11:19:26+00:00",
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
                    "selectedCurator": {
                        "id": "pjha",
                        "type": "senior-editor",
                        "name": {
                            "preferred": "Prabhat Jha",
                            "index": "Jha, Prabhat"
                        },
                        "etAl": true
                    }
                },
                {
                    "type": "interview",
                    "id": "1",
                    "interviewee": {
                        "name": {
                            "preferred": "Ramanath Hegde",
                            "index": "Hegde, Ramanath"
                        }
                    },
                    "title": "Controlling traffic",
                    "impactStatement": "Ramanath Hegde is a Postdoctoral Fellow at the Institute of Protein Biochemistry in Naples, Italy, where he investigates ways of preventing cells from destroying mutant proteins.",
                    "published": "2016-01-29T16:22:28+00:00"
                }
            ]
        }

        ';
        $minimum_expected = '
        {
          "total": 8,
          "items": [
            {
              "status": "poa",
              "volume": 5,
              "version": 1,
              "elocationId": "e14107",
              "doi": "10.7554\/eLife.14107",
              "type": "research-article",
              "published": "2016-03-28T00:00:00+00:00",
              "id": "14107",
              "title": "Molecular basis for multimerization in the activation of the epidermal growth factor"
            },
            {
              "status": "vor",
              "volume": 4,
              "version": 1,
              "elocationId": "e09560",
              "doi": "10.7554\/eLife.09560",
              "pdf": "https:\/\/elifesciences.org\/content\/4\/e09560.pdf",
              "type": "research-article",
              "published": "2015-09-10T00:00:00+00:00",
              "subjects": [
                "genomics-evolutionary-biology"
              ],
              "image": {
                "alt": "",
                "sizes": {
                  "2:1": {
                    "900": "https:\/\/placehold.it\/900x450",
                    "1800": "https:\/\/placehold.it\/1800x900"
                  },
                  "16:9": {
                    "250": "https:\/\/placehold.it\/250x141",
                    "500": "https:\/\/placehold.it\/500x281"
                  },
                  "1:1": {
                    "70": "https:\/\/placehold.it\/70x70",
                    "140": "https:\/\/placehold.it\/140x140"
                  }
                }
              },
              "id": "09560",
              "title": "<i>Homo naledi<\/i>, a new species of the genus <i>Homo<\/i> from the Dinaledi Chamber, South Africa",
              "impactStatement": "A new hominin species has been unearthed in the Dinaledi Chamber of the Rising Star cave system in the largest assemblage of a single species of hominins yet discovered in Africa."
            },
            {
              "published": "2016-07-08T08:33:25+00:00",
              "type": "blog-article",
              "id": "1",
              "title": "Media coverage: Slime can see",
              "impactStatement": "In their research paper \u2013 Cyanobacteria use micro-optics to sense light direction \u2013 Schuergers et al. reveal how bacterial cells act as the equivalent of a microscopic eyeball or the world\u2019s oldest and smallest camera eye, allowing them to \u2018see\u2019.",
              "subjects": [
                "biophysics-structural-biology"
              ]
            },
            {
              "number": 1,
              "type": "labs-experiment",
              "title": "Experimental eLife Lens search page",
              "image": {
                "alt": "",
                "sizes": {
                  "2:1": {
                    "900": "https:\/\/placehold.it\/900x450",
                    "1800": "https:\/\/placehold.it\/1800x900"
                  },
                  "16:9": {
                    "250": "https:\/\/placehold.it\/250x141",
                    "500": "https:\/\/placehold.it\/500x281"
                  },
                  "1:1": {
                    "70": "https:\/\/placehold.it\/70x70",
                    "140": "https:\/\/placehold.it\/140x140"
                  }
                }
              },
              "published": "2015-04-01T11:32:47+00:00"
            },
            {
              "number": 29,
              "sources": [
                {
                  "mediaType": "audio/mpeg",
                  "uri": "https://nakeddiscovery.com/scripts/mp3s/audio/eLife_Podcast_16.05.mp3"
                }
              ],
              "type": "podcast-episode",
              "title": "April\/May 2016",
              "published": "2016-05-27T13:19:42+00:00",
              "image": {
                "alt": "",
                "sizes": {
                  "2:1": {
                    "900": "https:\/\/placehold.it\/900x450",
                    "1800": "https:\/\/placehold.it\/1800x900"
                  },
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
            {
              "starts": "2016-04-22T20:00:00+00:00",
              "ends": "2016-04-22T21:00:00+00:00",
              "timezone": "America\/Seattle",
              "type": "event",
              "id": "1",
              "title": "Changing peer review in cancer research: a seminar at Fred Hutch",
              "impactStatement": "How eLife is influencing the culture of peer review"
            },
            {
              "updated": "2015-09-16T11:19:26+00:00",
              "selectedCurator": {
                "id": "pjha",
                "type": "senior-editor",
                "name": {
                  "preferred": "Prabhat Jha",
                  "index": "Jha, Prabhat"
                },
                "etAl": true
              },
              "type": "collection",
              "id": "1",
              "title": "Tropical disease",
              "image": {
                "alt": "",
                "sizes": {
                  "2:1": {
                    "900": "https:\/\/placehold.it\/900x450",
                    "1800": "https:\/\/placehold.it\/1800x900"
                  },
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
            {
              "type": "interview",
              "interviewee": {
                "name": {
                  "preferred": "Ramanath Hegde",
                  "index": "Hegde, Ramanath"
                }
              },
              "id": "1",
              "title": "Controlling traffic",
              "impactStatement": "Ramanath Hegde is a Postdoctoral Fellow at the Institute of Protein Biochemistry in Naples, Italy, where he investigates ways of preventing cells from destroying mutant proteins.",
              "published": "2016-01-29T16:22:28+00:00"
            }
          ],
          "subjects": [{"id":"biophysics-structural-biology","name":"Biophysics and Structural Biology","results":1}],
          "types": {
            "correction": 0,
            "editorial": 0,
            "feature": 0,
            "insight": 0,
            "research-advance": 0,
            "research-article": 2,
            "research-exchange": 0,
            "retraction": 0,
            "registered-report": 0,
            "replication-study": 0,
            "short-report": 0,
            "tools-resources": 0,
            "blog-article": 1,
            "collection": 1,
            "event": 1,
            "interview": 1,
            "labs-experiment": 1,
            "podcast-episode": 1
          }
        }
        ';

        return [
            [
                $minimum, $minimum_expected,
            ],
        ];
    }
}
