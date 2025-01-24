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
        $maxValue = $this->getTermsMaxValue();
        $strength = $maxValue;
        $significance = $maxValue;
        
        $maxLevel = function ($terms, callable $getValue) use ($maxValue) {
            return array_reduce($terms ?? [], fn($carry, $term) => max($carry, $getValue($term)), 0) ?: $maxValue - 1;
        };
        if ($object->getElifeAssessment()) {
            $strength = $maxLevel($object->getElifeAssessment()->getStrength(), [$this, 'getStrengthValue']);
            $significance = $maxLevel($object->getElifeAssessment()->getSignificance(), [$this, 'getSignificanceValue']);
        }

        return [
            'strength' => $strength,
            'significance' => $significance,
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
