<?php

namespace Spatie\UptimeMonitor\Events;

use Illuminate\Contracts\Queue\ShouldQueue;
use Spatie\UptimeMonitor\Helpers\Period;
use Spatie\UptimeMonitor\Models\Monitor;

class UptimeCheckRecovered extends BaseEvent
{
    public Period $downtimePeriod;

    public function __construct(Monitor $monitor, Period $downtimePeriod)
    {
        parent::__construct($monitor);
        $this->downtimePeriod = $downtimePeriod;
    }
}
