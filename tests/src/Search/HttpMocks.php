<?php

namespace tests\eLife\Search;

use eLife\ApiClient\ApiClient\SubjectsClient;
use eLife\ApiClient\MediaType;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use LogicException;

trait HttpMocks
{
    private function createSubjectJson()
    {
        return [
            'id' => 'subject1',
            'name' => 'Subject id name',
            'impactStatement' => 'Subject id impact statement',
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
                ['Accept' => (string) new MediaType(SubjectsClient::TYPE_SUBJECT, 1)]
            ),
            new Response(
                200,
                ['Content-Type' => (string) new MediaType(SubjectsClient::TYPE_SUBJECT, 1)],
                json_encode($this->createSubjectJson())
            )
        );
    }
}
