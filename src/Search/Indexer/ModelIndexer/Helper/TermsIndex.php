<?php

namespace eLife\Search\Indexer\ModelIndexer\Helper;

use eLife\ApiSdk\Model\HasElifeAssessment;

trait TermsIndex
{
    protected const TERMS_MAX_VALUE = 999;
    protected $strengthTerms = [
        'exceptional' => 6,
        'compelling' => 5,
        'convincing' => 4,
        'solid' => 3,
        'incomplete' => 2,
        'inadequate' => 1,
    ];
    protected $significanceTerms = [
        'landmark' => 5,
        'fundamental' => 4,
        'important' => 3,
        'valuable' => 2,
        'useful' => 1,
    ];

    public function termsIndexValues(HasElifeAssessment $object) {
        $strength = 0;
        $significance = 0;
        if ($object->getElifeAssessment()) {
            $strength = array_reduce($object->getElifeAssessment()->getStrength() ?? [], function($carry, $term) {
                return max($carry, $this->getStrengthValue($term));
            }, 0);
            $significance = array_reduce($object->getElifeAssessment()->getSignificance() ?? [], function($carry, $term) {
                return max($carry, $this->getSignificanceValue($term));
            }, 0);
        }

        return [
            'strength' => $strength > 0 ? $strength : $this->getTermsMaxValue(),
            'significance' => $significance > 0 ? $significance : $this->getTermsMaxValue(),
        ];
    }

    public function getTermsMaxValue() {
        return self::TERMS_MAX_VALUE;
    }
    
    public function getStrengthValue(string $strength) {
        return $this->strengthTerms[strtolower($strength)] ?? 0;
    }
    
    public function getSignificanceValue(string $significance) {
        return $this->significanceTerms[strtolower($significance)] ?? 0;
    }
}
