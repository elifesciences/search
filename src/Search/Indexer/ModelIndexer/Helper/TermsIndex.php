<?php

namespace eLife\Search\Indexer\ModelIndexer\Helper;

use eLife\ApiSdk\Model\ArticleVersion;
use eLife\ApiSdk\Model\HasElifeAssessment;

trait TermsIndex
{
    protected const TERMS_MAX_VALUE = 999;
    protected $significanceTerms = [
        'landmark' => 5,
        'fundamental' => 4,
        'important' => 3,
        'valuable' => 2,
        'useful' => 1,
    ];
    protected $strengthTerms = [
        'exceptional' => 6,
        'compelling' => 5,
        'convincing' => 4,
        'solid' => 3,
        'incomplete' => 2,
        'inadequate' => 1,
    ];
    // @note: might be better to just add significance and strength term ratings to all research articles and then allow client to specify which types 
    protected $articleTypes = [
        'research-article',
        'tools-resources',
        'short-report',
        'research-advance',
    ];

    public function termsIndexValues(HasElifeAssessment $object) {
        if ($object instanceof ArticleVersion && !in_array($object->getType(), $this->articleTypes)) {
            return null;
        }

        $significance = 0;
        $strength = 0;
        if ($object->getElifeAssessment()) {
            $significance = array_reduce($object->getElifeAssessment()->getSignificance() ?? [], function($carry, $term) {
                $value = $this->significanceTerms[strtolower($term)] ?? 0;
                return max($carry, $value);
            }, 0);
            $strength = array_reduce($object->getElifeAssessment()->getStrength() ?? [], function($carry, $term) {
                $value = $this->strengthTerms[strtolower($term)] ?? 0;
                return max($carry, $value);
            }, 0);
        }

        return [
            'significance' => $significance > 0 ? $significance: $this->getTermsMaxValue(),
            'strength' => $strength > 0 ? $strength: $this->getTermsMaxValue(),
        ];
    }

    public function getTermsMaxValue() {
        return self::TERMS_MAX_VALUE;
    }
}
