<?php

declare(strict_types=1);

namespace Headercat\Statimate\Console;

final class Timer
{
    /**
     * @var int Unix timestamp of starting time.
     */
    private int $startSecond;

    /**
     * @var float Microsecond part of starting time.
     */
    private float $startMicrosecond;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $start = explode(' ', microtime());
        $this->startSecond = (int) $start[1];
        $this->startMicrosecond = (float) $start[0];
    }

    /**
     * Get diff with starting time and current time.
     *
     * @return float
     */
    public function get(): float
    {
        $end = explode(' ', microtime());
        $endSecond = (int) $end[1];
        $endMicrosecond = (float) $end[0];
        $diffMicrosecond = (round($endMicrosecond * 100) - round($this->startMicrosecond * 100)) / 100;
        return ($endSecond - $this->startSecond) + $diffMicrosecond;
    }
}
