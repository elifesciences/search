<?php

namespace tests\eLife\Search\Api\Response;

use eLife\Search\Api\Response\ArticleResponse\PoaArticle;
use tests\eLife\Search\RamlRequirement;
use tests\eLife\Search\SerializerTest;

class PoaArticleResponseTest extends SerializerTest
{
    use RamlRequirement;
    public function getResponseClass() : string
    {
        return PoaArticle::class;
    }

    public function jsonProvider() : array
    {
        return [
            [
                $this->getFixture('article-poa/v1/minimum.json'), '{
                    "stage": "published",
                    "status": "poa",
                    "statusDate": "2016-03-28T00:00:00Z",
                    "volume": 5,
                    "version": 1,
                    "elocationId": "e14107",
                    "doi": "10.7554\/eLife.14107",
                    "type": "research-article",
                    "published": "2016-03-28T00:00:00Z",
                    "id": "14107",
                    "title": "Molecular basis for multimerization in the activation of the epidermal growth factor"
                }',
            ],
            [
                $this->getFixture('article-poa/v1/complete.json'), '
                {
                    "status": "poa",
                    "statusDate": "2016-03-28T00:00:00Z",
                    "volume": 5,
                    "version": 1,
                    "issue": 1,
                    "titlePrefix": "Title prefix",
                    "stage": "published",
                    "elocationId": "e14107",
                    "doi": "10.7554\/eLife.14107",
                    "authorLine": "Yongjian Huang et al",
                    "pdf": "https:\/\/elifesciences.org\/content\/5\/e14107.pdf",
                    "type": "research-article",
                    "published": "2016-03-28T00:00:00Z",
                    "subjects": [
                        {
                            "id": "biochemistry",
                            "name": "Biochemistry"
                        },
                        {
                            "id": "biophysics-structural-biology",
                            "name": "Biophysics and Structural Biology"
                        }
                    ],
                    "id": "14107",
                    "title": "Molecular basis for multimerization in the activation of the epidermal growth factor"
                }',
            ],
        ];
    }
}
