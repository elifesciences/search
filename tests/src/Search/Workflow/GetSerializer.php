<?php

namespace tests\eLife\Search\Workflow;

use eLife\ApiClient\ApiClient\SubjectsClient;
use eLife\ApiSdk\Client\Subjects;
use eLife\ApiSdk\Serializer\Block;
use eLife\ApiSdk\Serializer\BlogArticleNormalizer;
use eLife\ApiSdk\Serializer\ImageNormalizer;
use eLife\ApiSdk\Serializer\MediumArticleNormalizer;
use eLife\ApiSdk\Serializer\SubjectNormalizer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;
use tests\eLife\Search\HttpClient;

trait GetSerializer
{
    use HttpClient;

    private $serializer;

    public function getSerializer()
    {
        if ($this->serializer !== null) {
            return $this->serializer;
        }
        $this->serializer = new Serializer([
            $blogArticleNormalizer = new BlogArticleNormalizer(),
            new ImageNormalizer(),
            new MediumArticleNormalizer(),
            new SubjectNormalizer(),
            new Block\ImageNormalizer(),
            new Block\ParagraphNormalizer(),
            new Block\YouTubeNormalizer(),
        ], [new JsonEncoder()]);

        $blogArticleNormalizer->setSubjects(new Subjects(new SubjectsClient($this->getHttpClient()), $this->serializer));

        return $this->serializer;
    }
}
