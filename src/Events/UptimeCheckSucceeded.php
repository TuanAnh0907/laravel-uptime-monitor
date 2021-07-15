<?php

namespace Spatie\UptimeMonitor\Events;

use Illuminate\Contracts\Queue\ShouldQueue;
use Spatie\UptimeMonitor\Models\Monitor;

class UptimeCheckSucceeded extends BaseEvent
{
    public function __construct(Monitor $monitor)
    {
        parent::__construct($monitor);
    }
}
