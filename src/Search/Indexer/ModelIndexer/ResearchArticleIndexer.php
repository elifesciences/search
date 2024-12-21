<?php

namespace eLife\Search\Indexer\ModelIndexer;

use DateTimeImmutable;
use eLife\ApiSdk\Model\ArticleVersion;
use eLife\ApiSdk\Model\ArticleVoR;
use eLife\ApiSdk\Model\Model;
use eLife\Search\Indexer\ChangeSet;
use RuntimeException;
use Symfony\Component\Serializer\Serializer;

final class ResearchArticleIndexer extends AbstractModelIndexer
{
    public function __construct(
        Serializer $serializer,
        private array $rdsArticles = []
    ) {
        parent::__construct($serializer);
    }

    protected function getSdkClass(): string
    {
        return ArticleVersion::class;
    }

    /**
     * @param ArticleVersion $article
     * @return ChangeSet
     */
    public function prepareChangeSet(Model $article) : ChangeSet
    {
        $changeSet = new ChangeSet();

        $articleObject = json_decode($this->serialize($article));
        // Fix author name.
        $articleObject->authors = array_map(function ($author) {
            // @todo - investigate whether it is right to assume that name property exists.
            if (is_string($author->name ?? null)) {
                $author->name = ['value' => $author->name];
            }

            return $author;
        }, $articleObject->authors ?? []);
        // Fix author name in references.
        $articleObject->references = array_map(function ($reference) {
            $reference->authors = array_map(function ($author) {
                if (is_string($author->name)) {
                    $author->name = ['value' => $author->name];
                }

                return $author;
            }, $reference->authors ?? []);

            return $reference;
        }, $articleObject->authors);
        $articleObject->abstract = $this->flattenBlocks($articleObject->abstract->content ?? []);
        $articleObject->digest = $this->flattenBlocks($articleObject->digest->content ?? []);
        $articleObject->body = $this->flattenBlocks($articleObject->body ?? []);
        if ($article instanceof ArticleVoR) {
            $articleObject->body .= $this->flattenBlocks($articleObject->publicReviews ?? []);
            $articleObject->body .= $this->flattenBlocks($articleObject->elifeAssessment->content ?? []);
            $articleObject->body .= $this->flattenBlocks($articleObject->recommendationsForAuthors->content ?? []);
            unset($articleObject->publicReviews);
            unset($articleObject->elifeAssessment);
            unset($articleObject->recommendationsForAuthors);
        }
        if (!empty($articleObject->appendices)) {
            $appendices = '';
            foreach ($articleObject->appendices ?? [] as $appendix) {
                $appendices .= $this->flattenBlocks($appendix->content ?? []);
            }
            $articleObject->appendices = $appendices;
        }
        $articleObject->acknowledgements = $this->flattenBlocks($articleObject->acknowledgements ?? []);
        $articleObject->decisionLetter = $this->flattenBlocks($articleObject->decisionLetter->content ?? []);
        $articleObject->authorResponse = $this->flattenBlocks($articleObject->authorResponse->content ?? []);
        // Completely serialize funding
        $articleObject->funding = [
            'format' => 'json',
            'value' => json_encode($articleObject->funding ?? '[]'),
        ];
        // Completely serialize dataSets
        $articleObject->dataSets = [
            'format' => 'json',
            'value' => json_encode($articleObject->dataSets ?? '[]'),
        ];

        $snippet = $this->snippet($article);

        if ($article instanceof ArticleVoR) {
            $changeSet->addDelete('reviewed-preprint-'.$article->getId());
        }

        $articleObject->snippet = ['format' => 'json', 'value' => json_encode($snippet)];

        if (isset($this->rdsArticles[$article->getId()]['date'])) {
            $sortDate = DateTimeImmutable::createFromFormat(DATE_ATOM, $this->rdsArticles[$article->getId()]['date']);
            if (false === $sortDate) {
                throw new RuntimeException($this->rdsArticles[$article->getId()]['date'].' is not a valid date');
            }
        } else {
            $sortDate = $article->getStatusDate();
        }
        $this->addSortDate($articleObject, $sortDate);

        $changeSet->addInsert(
            $articleObject->type.'-'.$article->getId(),
            json_encode($articleObject),
        );
        return $changeSet;
    }
}
