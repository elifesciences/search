<?php

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->exclude('pattern-library')
    ->name('update')
;

return Symfony\CS\Config\Config::create()
    ->level(Symfony\CS\FixerInterface::SYMFONY_LEVEL)
    ->fixers(['-empty_return', 'ordered_use'])
    ->finder($finder)
;
