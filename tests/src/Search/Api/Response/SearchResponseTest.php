<?php

namespace tests\eLife\Search\Api\Response;

use eLife\Search\Api\Response\SearchResponse;
use tests\eLife\Search\RamlRequirement;
use tests\eLife\Search\SerializerTest;

class SearchResponseTest extends SerializerTest
{
    use RamlRequirement;

    public function getResponseClass() : string
    {
        return SearchResponse::class;
    }

    public function jsonProvider() : array
    {
        return [
            [
                $this->getFixture('search/v1/first-page.json'), '
                {
                    "total": 7,
                    "items": [
                        {
                            "status": "poa",
                            "statusDate": "2016-03-28T00:00:00Z",
                            "stage": "published",
                            "volume": 5,
                            "version": 1,
                            "elocationId": "e14107",
                            "doi": "10.7554\/eLife.14107",
                            "authorLine": "Yongjian Huang et al",
                            "type": "research-article",
                            "published": "2016-03-28T00:00:00Z",
                            "id": "14107",
                            "title": "Molecular basis for multimerization in the activation of the epidermal growth factor"
                        },
                        {
                            "status": "vor",
                            "statusDate": "2015-09-10T00:00:00Z",
                            "stage": "published",
                            "volume": 4,
                            "version": 1,
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
                        },
                        {
                            "published": "2016-07-08T08:33:25Z",
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
                        },
                        {
                            "id": "80000001",
                            "type": "labs-post",
                            "title": "Experimental eLife Lens search page",
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
                            },
                            "published": "2015-04-01T11:32:47Z"
                        },
                        {
                            "number": 29,
                            "sources": [
                                {
                                    "mediaType": "audio\/mpeg",
                                    "uri": "https:\/\/nakeddiscovery.com\/scripts\/mp3s\/audio\/eLife_Podcast_16.05.mp3"
                                }
                            ],
                            "type": "podcast-episode",
                            "title": "April\/May 2016",
                            "published": "2016-05-27T13:19:42Z",
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
                        },
                        {
                            "selectedCurator": {
                                "id": "pjha",
                                "type": {
                                    "id": "senior-editor",
                                    "label": "Senior Editor"
                                },
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
                            },
                            "published": "2015-09-16T11:19:26Z",
                            "updated": "2015-09-16T11:19:26Z"
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
                            "published": "2016-01-29T16:22:28Z"
                        }
                    ],
                    "types": {
                        "correction": 0,
                        "editorial": 1,
                        "feature": 0,
                        "insight": 0,
                        "research-advance": 1,
                        "research-article": 2,
                        "retraction": 0,
                        "registered-report": 0,
                        "replication-study": 0,
                        "scientific-correspondence": 0,
                        "short-report": 0,
                        "tools-resources": 0,
                        "blog-article": 3,
                        "collection": 2,
                        "interview": 2,
                        "labs-post": 2,
                        "podcast-episode": 1
                    },
                    "subjects": [
                        {
                            "id": "biophysics-structural-biology",
                            "name": "Biophysics and Structural Biology",
                            "results": 1
                        },
                        {
                            "id": "cell-biology",
                            "name": "Cell Biology",
                            "results": 3
                        },
                        {
                            "id": "genomics-evolutionary-biology",
                            "name": "Genomics and Evolutionary Biology",
                            "results": 3
                        }
                    ]
                }',
            ],
        ];
    }
}
