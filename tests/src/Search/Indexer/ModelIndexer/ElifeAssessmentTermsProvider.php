<?php

namespace tests\eLife\Search\Indexer\ModelIndexer;

use Traversable;

trait ElifeAssessmentTermsProvider
{
    public static function elifeAssessmentTermsProvider() : Traversable
    {
        $elifeAssessment = function (array $terms) {
            return [
                'elifeAssessment' => [
                    'title' => 'eLife Assessment',
                    'content' => [
                        [
                            'type' => 'paragraph',
                            'text' => 'eLife Assessment content',
                        ],
                    ],
                    ...$terms,
                ],
            ];
        };
        
        yield "no elife assessment" => [
            [],
            [
                'significance' => 999,
                'strength' => 999,
            ],
        ];
        
        yield "elife assessment, no terms" => [
            $elifeAssessment([]),
            [
                'significance' => 0,
                'strength' => 0,
            ],
        ];
        
        yield "elife assessment, no strength" => [
            $elifeAssessment([
                'significance' => ['valuable'],
            ]),
            [
                'significance' => 2,
                'strength' => 0,
            ],
        ];
        
        yield "elife assessment, no significance" => [
            $elifeAssessment([
                'strength' => ['solid'],
            ]),
            [
                'significance' => 0,
                'strength' => 3,
            ],
        ];
        
        yield "basic" => [
            $elifeAssessment([
                'significance' => ['valuable'],
                'strength' => ['solid'],
            ]),
            [
                'significance' => 2,
                'strength' => 3,
            ],
        ];
        
        yield "multiple" => [
            $elifeAssessment([
                'significance' => ['valuable', 'fundamental'],
                'strength' => ['exceptional', 'solid'],
            ]),
            [
                'significance' => 4,
                'strength' => 6,
            ],
        ];
    }
}
