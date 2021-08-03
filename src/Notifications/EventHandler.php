<?php

namespace Spatie\UptimeMonitor\Notifications;

use GuzzleHttp\Client;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;
use Spatie\UptimeMonitor\Events\CertificateCheckFailed;
use Spatie\UptimeMonitor\Events\CertificateCheckSucceeded;
use Spatie\UptimeMonitor\Events\CertificateExpiresSoon;
use Spatie\UptimeMonitor\Events\UptimeCheckFailed;
use Spatie\UptimeMonitor\Events\UptimeCheckRecovered;
use Spatie\UptimeMonitor\Events\UptimeCheckSucceeded;
use Spatie\UptimeMonitor\Models\Monitor;

class EventHandler
{
    /** @var \Illuminate\Config\Repository */
    protected $config;

    public function __construct(Repository $config)
    {
        $this->config = $config;
    }

    public function subscribe(Dispatcher $events)
    {
        $events->listen($this->allEventClasses(), function ($event) {
            $notification = $this->determineNotification($event);

            if (!$notification) {
                return;
            }

            if ($notification->isStillRelevant()) {
                $notifiable = $this->determineNotifiable($event->getEmails(), $event->getSlackChannel());

                $notifiable->notify($notification);
            }

            if ($webhook = $event->getWebhook()) {
                (new Client($this->webhookHeaders($monitor = $event->monitor)))->post($webhook, [
                    "form_params" => [
                        "monitor" => Arr::only($monitor->toArray(), [
                            "url",
                            "uptime_status",
                            "uptime_last_check_date",
                            "certificate_status"
                        ])
                    ]
                ]);
            }
        });
    }

    private function webhookHeaders(Monitor $monitor): array
    {
        return collect([])
            ->merge(['User-Agent' => config('uptime-monitor.uptime_check.user_agent')])
            ->merge(config('uptime-monitor.uptime_check.additional_headers') ?? [])
            ->merge($monitor->uptime_check_additional_headers)
            ->toArray();
    }

    protected function determineNotifiable($emails = [], $slack_channel = "")
    {
        $notifiableClass = $this->config->get('uptime-monitor.notifications.notifiable');

        return app($notifiableClass, ["emails" => $emails, "slack_channel" => $slack_channel]);
    }

    protected function determineNotification($event)
    {
        $eventName = class_basename($event);

        $notificationClass = collect($this->config->get('uptime-monitor.notifications.notifications'))
            ->filter(function (array $notificationChannels) {
                return count($notificationChannels);
            })
            ->keys()
            ->first(function ($notificationClass) use ($eventName) {
                $notificationName = class_basename($notificationClass);

                return $notificationName === $eventName;
            });

        if ($notificationClass) {
            return app($notificationClass)->setEvent($event);
        }
    }

    protected function allEventClasses(): array
    {
        return [
            UptimeCheckFailed::class,
            UptimeCheckSucceeded::class,
            UptimeCheckRecovered::class,
            CertificateCheckSucceeded::class,
            CertificateCheckFailed::class,
            CertificateExpiresSoon::class,
        ];
    }
}
