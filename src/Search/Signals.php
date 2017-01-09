<?php


namespace eLife\Search;


class Signals
{

    private static $isValid = true;

    public static function register()
    {
        if (function_exists('pcntl_signal')) {
            // Signals
            pcntl_signal(SIGTERM, [Signals::class, 'onTermination']);
            pcntl_signal(SIGHUP, [Signals::class, 'onTermination']);
        }
    }

    public static function tick()
    {
        if (function_exists('pcntl_signal_dispatch')) {
            pcntl_signal_dispatch();
        }
    }

    public static function onTermination()
    {
        self::$isValid = false;
    }

    public static function isValid()
    {
        return self::$isValid;
    }



}
