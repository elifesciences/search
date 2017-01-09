<?php

namespace eLife\Search;

class Signals
{
    private static $isValid = true;

    public static function register()
    {
        if (function_exists('pcntl_signal')) {
            // Signals
            pcntl_signal(SIGTERM, [self::class, 'onTermination']);
            pcntl_signal(SIGHUP, [self::class, 'onTermination']);
        }
    }

    public static function isValid() : bool
    {
        if (function_exists('pcntl_signal_dispatch')) {
            pcntl_signal_dispatch();
        }

        return self::$isValid;
    }

    public static function onTermination()
    {
        self::$isValid = false;
    }
}
