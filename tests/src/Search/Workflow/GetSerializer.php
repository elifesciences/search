<?php

namespace tests\eLife\Search\Workflow;

use eLife\ApiClient\ApiClient\ArticlesClient;
use eLife\ApiClient\ApiClient\BlogClient;
use eLife\ApiClient\ApiClient\EventsClient;
use eLife\ApiClient\ApiClient\InterviewsClient;
use eLife\ApiClient\ApiClient\LabsClient;
use eLife\ApiClient\ApiClient\PeopleClient;
use eLife\ApiClient\ApiClient\SubjectsClient;
use eLife\ApiSdk\Client\Subjects;
use eLife\ApiSdk\Serializer\AddressNormalizer;
use eLife\ApiSdk\Serializer\AnnualReportNormalizer;
use eLife\ApiSdk\Serializer\ArticlePoANormalizer;
use eLife\ApiSdk\Serializer\ArticleVoRNormalizer;
use eLife\ApiSdk\Serializer\Block;
use eLife\ApiSdk\Serializer\BlogArticleNormalizer;
use eLife\ApiSdk\Serializer\EventNormalizer;
use eLife\ApiSdk\Serializer\GroupAuthorNormalizer;
use eLife\ApiSdk\Serializer\ImageNormalizer;
use eLife\ApiSdk\Serializer\InterviewNormalizer;
use eLife\ApiSdk\Serializer\LabsExperimentNormalizer;
use eLife\ApiSdk\Serializer\MediumArticleNormalizer;
use eLife\ApiSdk\Serializer\OnBehalfOfAuthorNormalizer;
use eLife\ApiSdk\Serializer\PersonAuthorNormalizer;
use eLife\ApiSdk\Serializer\PersonNormalizer;
use eLife\ApiSdk\Serializer\PlaceNormalizer;
use eLife\ApiSdk\Serializer\Reference;
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
            new AddressNormalizer(),
            new AnnualReportNormalizer(),
            new ArticlePoANormalizer(new ArticlesClient($this->getHttpClient())),
            new ArticleVoRNormalizer(new ArticlesClient($this->getHttpClient())),
            new BlogArticleNormalizer(new BlogClient($this->getHttpClient())),
            new EventNormalizer(new EventsClient($this->getHttpClient())),
            new GroupAuthorNormalizer(),
            new ImageNormalizer(),
            new InterviewNormalizer(new InterviewsClient($this->getHttpClient())),
            new LabsExperimentNormalizer(new LabsClient($this->getHttpClient())),
            new MediumArticleNormalizer(),
            new OnBehalfOfAuthorNormalizer(),
            new PersonAuthorNormalizer(),
            new PersonNormalizer(new PeopleClient($this->getHttpClient())),
            new PlaceNormalizer(),
            new SubjectNormalizer(new SubjectsClient($this->getHttpClient())),
            new Block\BoxNormalizer(),
            new Block\FileNormalizer(),
            new Block\ImageNormalizer(),
            new Block\ListingNormalizer(),
            new Block\MathMLNormalizer(),
            new Block\ParagraphNormalizer(),
            new Block\QuestionNormalizer(),
            new Block\QuoteNormalizer(),
            new Block\SectionNormalizer(),
            new Block\TableNormalizer(),
            new Block\VideoNormalizer(),
            new Block\YouTubeNormalizer(),
            new Reference\BookReferenceNormalizer(),
            new Reference\BookChapterReferenceNormalizer(),
            new Reference\ClinicalTrialReferenceNormalizer(),
            new Reference\ConferenceProceedingReferenceNormalizer(),
            new Reference\DataReferenceNormalizer(),
            new Reference\JournalReferenceNormalizer(),
            new Reference\PatentReferenceNormalizer(),
            new Reference\PeriodicalReferenceNormalizer(),
            new Reference\PreprintReferenceNormalizer(),
            new Reference\ReferencePagesNormalizer(),
            new Reference\ReportReferenceNormalizer(),
            new Reference\SoftwareReferenceNormalizer(),
            new Reference\ThesisReferenceNormalizer(),
            new Reference\WebReferenceNormalizer(),
        ], [new JsonEncoder()]);
        // Add subjects client mock.
        $subjectsClient = new Subjects(new SubjectsClient($this->getHttpClient()), $this->serializer);
//        $articlePoANormalizer->setSubjects($subjectsClient);
//        $articleVoRNormalizer->setSubjects($subjectsClient);
//        $blogArticleNormalizer->setSubjects($subjectsClient);

        return $this->serializer;
    }
}
