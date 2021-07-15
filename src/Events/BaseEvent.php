<?php
/**
 * Created by PhpStorm.
 * User: Hiệp Nguyễn
 * Date: 15/07/2021
 * Time: 08:56
 */


namespace Spatie\UptimeMonitor\Events;

use \Illuminate\Contracts\Queue\ShouldQueue;
use Spatie\UptimeMonitor\Models\Monitor;

class BaseEvent implements ShouldQueue
{
    /**
     * @var Monitor
     */
    public Monitor $monitor;

    /**
     * BaseEvent constructor.
     * @param Monitor $monitor
     */
    public function __construct(Monitor $monitor)
    {
        $this->monitor = $monitor;
    }

    /**
     * @return array|false|string[]
     */
    public function getEmails(){
        $emails = $this->monitor->user_emails ?? "";
        if ($emails){
            return explode(",",$emails);
        }
        return [];
    }

    public function getSlackUserIds(){
        $user_ids = $this->monitor->slack_user_ids ?? "";
        if ($user_ids){
            return explode(",",$user_ids);
        }
        return  [];
    }
}
