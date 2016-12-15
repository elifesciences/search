<?php

namespace tests\eLife\Search\Api\Response;

use eLife\Search\Api\Response\InterviewResponse;
use tests\eLife\Search\RamlRequirement;
use tests\eLife\Search\SerializerTest;

class InterviewResponseTest extends SerializerTest
{
    use RamlRequirement;

    public function getResponseClass() : string
    {
        return InterviewResponse::class;
    }

    public function jsonProvider() : array
    {
        return [
            [
                $this->getFixture('interview/v1/minimum.json'), '
                {
                    "id": "2",
                    "type": "interview",
                    "interviewee": {
                        "name": {
                            "preferred": "Alicia Rosello",
                            "index": "Rosello, Alicia"
                        }
                    },
                    "title": "Infection, statistics and public health",
                    "published": "2015-11-03T11:00:53Z"
                }',
            ],
            [
                $this->getFixture('interview/v1/complete.json'), '
                {
                    "id": "1",
                    "type": "interview",
                    "interviewee": {
                        "name": {
                            "preferred": "Ramanath Hegde",
                            "index": "Hegde, Ramanath"
                        }
                    },
                    "title": "Controlling traffic",
                    "impactStatement": "Ramanath Hegde is a Postdoctoral Fellow at the Institute of Protein Biochemistry in Naples, Italy, where he investigates ways of preventing cells from destroying mutant proteins.",
                    "published": "2016-01-29T16:22:28Z"
                }',
            ],
        ];
    }
}
