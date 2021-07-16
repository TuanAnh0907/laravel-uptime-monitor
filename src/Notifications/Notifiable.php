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

    /**
     * @var mixed|string
     */
    protected $slack_channel;

    public function __construct($emails = [], $slack_channel = "")
    {
        $this->emails        = $emails;
        $this->slack_channel = $slack_channel;
    }

    /**
     * @param array $emails
     * @return array|\Illuminate\Config\Repository|\Illuminate\Contracts\Foundation\Application
     */
    public function routeNotificationForMail()
    {
        return $this->emails ?: config('uptime-monitor.notifications.mail.to', []);
    }

    /**
     * @return string|null
     */
    public function routeNotificationForSlack()
    {
        return $this->slack_channel ?: config('uptime-monitor.notifications.slack.webhook_url');
    }

    public function getKey(): string
    {
        return static::class;
    }
}
