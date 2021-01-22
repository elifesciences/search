<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->name('console')
;

return PhpCsFixer\Config::create()
    ->setRules([
        '@Symfony' => true,
        'simplified_null_return' => false,
        'ordered_imports' => true,
        'return_type_declaration' => ['space_before' => 'one'],
    ])
    ->setUsingCache(true)
    ->setFinder($finder)
;
