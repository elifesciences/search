<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('pattern-library')
    ->name('update')
    ->in(__DIR__)
;

return PhpCsFixer\Config::create()
    ->setRules([
        '@Symfony' => true,
        'simplified_null_return' => false,
        'ordered_imports' => true,
        'return_type_declaration' => ['space_before' => 'one'],
    ])
    ->setFinder($finder)
;
