<?php

namespace eLife\Search\Gearman;

class SignalsLimit
{
    private static $validSignals;
    private $valid;

    public function __construct($signals)
    {
        if (function_exists('pcntl_signal')) {
            self::$validSignals = [
                'SIGINT' => SIGINT,
                'SIGTERM' => SIGTERM,
                'SIGHUP' => SIGHUP,
            ];

            foreach ($signals as $signal) {
                // Signals
                pcntl_signal(self::$validSignals[$signal], [$this, 'onTermination']);
            }
        }
    }

    public function onTermination()
    {
        $this->valid = false;
    }

    public static function sigterm(array $signals) : self
    {
        return new static($signals);
    }

    public function __invoke() : bool
    {
        if (function_exists('pcntl_signal_dispatch')) {
            pcntl_signal_dispatch();
        }

        return $this->valid === false;
    }
}
