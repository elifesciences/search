<?php

namespace tests\eLife\Search\Api\Response;

use eLife\Search\Api\Response\CollectionResponse;
use tests\eLife\Search\RamlRequirement;
use tests\eLife\Search\SerializerTest;

class CollectionResponseTest extends SerializerTest
{
    use RamlRequirement;

    public function testDeserialization()
    {
        $data = [
            'id' => '1',
            'title' => 'Tropical disease',
            'impactStatement' => 'eLife has published papers on many tropical diseases, including malaria, Ebola, leishmaniases, Dengue and African sleeping sickness.',
            'image' => [
                'banner' => [
                    'alt' => '',
                    'uri' => 'https://iiif.elifesciences.org/banner.jpg',
                    'source' => [
                        'mediaType' => 'image/jpeg',
                        'uri' => 'https://iiif.elifesciences.org/banner.jpg/full/full/0/default.jpg',
                        'filename' => 'banner.jpg',
                    ],
                    'size' => [
                        'width' => 800,
                        'height' => 400,
                    ],
                    'focalPoint' => null,
                    'attribution' => ['by Picasso'],
                ],
                'thumbnail' => [
                    'alt' => '',
                    'uri' => 'https://iiif.elifesciences.org/thumbnail.jpg',
                    'source' => [
                        'mediaType' => 'image/jpeg',
                        'uri' => 'https://iiif.elifesciences.org/thumbnail.jpg/full/full/0/default.jpg',
                        'filename' => 'thumbnail.jpg',
                    ],
                    'size' => [
                        'width' => 140,
                        'height' => 140,
                    ],
                    'focalPoint' => null,
                    'attribution' => [],
                ],
            ],
            'published' => '2015-09-16T11:19:26Z',
            'selectedCurator' => [
                'etAl' => true,
                'id' => 'pjha',
                'type' => [
                    'id' => 'senior-editor',
                    'label' => 'Senior Editor',
                ],
                'name' => [
                    'preferred' => 'Prabhat Jha',
                    'index' => 'Jha, Prabhat',
                ],
                'image' => null,
            ],
        ];
        $collection = $this->responseFromArray(CollectionResponse::class, $data);

        $this->assertSame('1', $collection->id);
        $this->assertSame('Tropical disease', $collection->title);
        $this->assertSame($data['image']['thumbnail'], (array) $collection->image->thumbnail);
        $this->assertSame($data['image']['banner'], (array) $collection->image->banner);
        $this->assertSame('2015-09-16T11:19:26Z', $collection->published->format('Y-m-d\TH:i:s\Z'));
        $this->assertEquals($data['selectedCurator'], (array) $collection->selectedCurator);
        $this->assertSame($data['selectedCurator']['id'], $collection->selectedCurator->id);
        $this->assertSame($data['selectedCurator']['type'], (array) $collection->selectedCurator->type);
        $this->assertSame($data['selectedCurator']['etAl'], $collection->selectedCurator->etAl);
        $this->assertSame($data['selectedCurator']['name']['index'], $collection->selectedCurator->name['index']);
        $this->assertSame($data['selectedCurator']['name']['preferred'], $collection->selectedCurator->name['preferred']);
        $this->assertSame($data['selectedCurator']['image'], $collection->selectedCurator->image);
    }

    public function getResponseClass() : string
    {
        return CollectionResponse::class;
    }

    public function jsonProvider() : array
    {
        return [
            [
                $this->getFixture('collection/v1/minimum.json'), '
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
                        "etAl": false
                    },
                    "type": "collection",
                    "id": "1",
                    "title": "Tropical disease",
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
                            "attribution": [
                                "By some person."
                            ]
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
                            }
                        }
                    },
                    "published": "2015-09-16T11:19:26Z"
                }',
            ],
            [
                $this->getFixture('collection/v1/complete.json'),
                '
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
                    "impactStatement": "eLife has published papers on many tropical diseases, including malaria, Ebola, leishmaniases, Dengue and African sleeping sickness.",
                    "subjects": [
                        {
                            "id": "epidemiology-global-health",
                            "name": "Epidemiology and Global Health"
                        },
                        {
                            "id": "microbiology-infectious-disease",
                            "name": "Microbiology and Infectious Disease"
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
                    "published": "2015-09-16T11:19:26Z",
                    "updated": "2015-09-17T11:19:26Z"
                }',
            ],
        ];
    }
}
