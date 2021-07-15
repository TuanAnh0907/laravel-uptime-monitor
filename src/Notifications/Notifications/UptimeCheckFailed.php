<?php

namespace Spatie\UptimeMonitor\Notifications\Notifications;

use Carbon\Carbon;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackAttachment;
use Illuminate\Notifications\Messages\SlackMessage;
use Spatie\UptimeMonitor\Events\UptimeCheckFailed as MonitorFailedEvent;
use Spatie\UptimeMonitor\Models\Enums\UptimeStatus;
use Spatie\UptimeMonitor\Notifications\BaseNotification;

class UptimeCheckFailed extends BaseNotification
{
    public MonitorFailedEvent $event;

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {

        return (new MailMessage)
            ->subject($this->getMessageText())
            ->markdown('emails.report',
                [
                    'properties' => $this->getMonitorProperties(),
                    'monitor'    => $this->getMonitor()
                ]);
    }

    public function toSlack($notifiable)
    {
        $title = "";
        foreach ($this->event->getSlackUserIds() as $id) {
            $title .= "<@$id> ";
        }
        $title .= $this->getMessageText();
        return (new SlackMessage)
            ->error()
            ->attachment(function (SlackAttachment $attachment) use ($title) {
                $attachment
                    ->title($title)
                    ->content($this->getMonitor()->uptime_check_failure_reason)
                    ->fallback($this->getMessageText())
                    ->footer($this->getLocationDescription())
                    ->timestamp(Carbon::now());
            });
    }

    public function getMonitorProperties($extraProperties = []): array
    {
        $extraProperties = [
            'Failure reason' => $this->getMonitor()->uptime_check_failure_reason,
        ];
        return parent::getMonitorProperties($extraProperties);
    }

    public function isStillRelevant(): bool
    {
        return $this->getMonitor()->uptime_status == UptimeStatus::DOWN;
    }

    public function setEvent(MonitorFailedEvent $event): self
    {
        $this->event = $event;

        return $this;
    }

    protected function getMessageText(): string
    {
        return "Monitor {$this->getMonitor()->name} seems down";
    }
}
