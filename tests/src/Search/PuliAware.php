<?php

namespace tests\eLife\Search;

trait PuliAware
{
    /**
     * @var ResourceRepository
     */
    private static $puli;

    /**
     * @beforeClass
     */
    final public static function setUpPuli()
    {
        $factoryClass = PULI_FACTORY_CLASS;
        self::$puli = (new $factoryClass())->createRepository();
    }
}
