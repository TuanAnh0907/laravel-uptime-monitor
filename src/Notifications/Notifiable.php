<?php

namespace Spatie\UptimeMonitor\Notifications;

use Illuminate\Notifications\Notifiable as NotifiableTrait;

class Notifiable
{
    use NotifiableTrait;

    /**
     * @var array
     */
    protected $emails;

    public function __construct($emails = [])
    {
        $this->emails = $emails;
    }

    /**
     * @param array $emails
     * @return array|\Illuminate\Config\Repository|\Illuminate\Contracts\Foundation\Application
     */
    public function routeNotificationForMail()
    {
        dd(array_unique(array_merge(config('uptime-monitor.notifications.mail.to', []), $this->emails)));
        return array_unique(array_merge(config('uptime-monitor.notifications.mail.to', []), $this->emails));
    }

    /**
     * @return string|null
     */
    public function routeNotificationForSlack()
    {
        return config('uptime-monitor.notifications.slack.webhook_url');
    }

    public function getKey(): string
    {
        return static::class;
    }
}
