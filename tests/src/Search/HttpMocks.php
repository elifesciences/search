<?php

namespace tests\eLife\Search;

use eLife\ApiClient\ApiClient\SubjectsClient;
use eLife\ApiClient\MediaType;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use LogicException;

trait HttpMocks
{
    final private function createSubjectJson()
    {
        return [
            'id' => 'subject1',
            'name' => 'Subject id name',
            'impactStatement' => 'Subject id impact statement',
            'image' => [
                'banner' => [
                    'alt' => 'this is an alt',
                    'sizes' => [
                        '2:1' => [
                            '900' => 'https://placehold.it/900x450',
                            '1800' => 'https://placehold.it/1800x900',
                        ],
                    ],
                ],
                'thumbnail' => [
                    'alt' => 'this is an alt',
                    'sizes' => [
                        '16:9' => [
                            '250' => 'https://placehold.it/250x141',
                            '500' => 'https://placehold.it/500x281',
                        ],
                        '1:1' => [
                            '70' => 'https://placehold.it/70x70',
                            '140' => 'https://placehold.it/140x140',
                        ],
                    ],
                ],
            ],

        ];
    }

    private function mockSubjects()
    {
        if (!isset($this->storage)) {
            throw new LogicException('You need to include the HttpClient trait to mock Http Requests.');
        }
        $this->storage->save(
            new Request(
                'GET',
                'http://api.elifesciences.org/subjects/subject1',
                ['Accept' => new MediaType(SubjectsClient::TYPE_SUBJECT, 1)]
            ),
            new Response(
                200,
                ['Content-Type' => new MediaType(SubjectsClient::TYPE_SUBJECT, 1)],
                json_encode($this->createSubjectJson())
            )
        );
    }
}
