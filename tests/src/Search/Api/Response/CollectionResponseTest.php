<?php

namespace tests\eLife\Search\Api\Response;

use eLife\Search\Api\Response\CollectionResponse;
use tests\eLife\Search\SerializerTest;

class CollectionResponseTest extends SerializerTest
{
    public function testDeserialization()
    {
        $data = [
            'id' => '1',
            'title' => 'Tropical disease',
            'impactStatement' => 'eLife has published papers on many tropical diseases, including malaria, Ebola, leishmaniases, Dengue and African sleeping sickness. The articles below have been selected by eLife editors to give a flavour of the breadth of research on tropical diseases published by the journal.',
            'updated' => '2015-09-16T11:19:26+00:00',
            'image' => [
                'alt' => '',
                'sizes' => [
                    '2:1' => [
                        900 => 'https://placehold.it/900x450',
                        1800 => 'https://placehold.it/1800x900',
                    ],
                    '16:9' => [
                        250 => 'https://placehold.it/250x141',
                        500 => 'https://placehold.it/500x281',
                    ],
                    '1:1' => [
                        70 => 'https://placehold.it/70x70',
                        140 => 'https://placehold.it/140x140',
                    ],
                ],
            ],
            'selectedCurator' => [
                'etAl' => true,
                'id' => 'pjha',
                'type' => 'senior-editor',
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
        $this->assertSame($data['image'], (array) $collection->image);
        $this->assertSame('2015-09-16T11:19:26+00:00', $collection->updated->format('c'));
        $this->assertSame($data['selectedCurator'], (array) $collection->selectedCurator);
    }

    public function getResponseClass() : string
    {
        return CollectionResponse::class;
    }

    public function jsonProvider() : array
    {
        $actual = '
        {
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
                }
            },
            "curators": [
                {
                    "id": "pjha",
                    "type": "senior-editor",
                    "name": {
                        "preferred": "Prabhat Jha",
                        "index": "Jha, Prabhat"
                    }
                }
            ],
            "content": [
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
                }
            ]
        }

        ';
        $expected = '
        {
            "id": "1",
            "type": "collection",
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
                "etAl": false
            }
        }
        ';

        return [
            [
                $actual, $expected,
            ],
        ];
    }
}
