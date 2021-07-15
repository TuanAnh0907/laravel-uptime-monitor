<?php

namespace Spatie\UptimeMonitor\Notifications\Notifications;

use Carbon\Carbon;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackAttachment;
use Illuminate\Notifications\Messages\SlackMessage;
use Spatie\UptimeMonitor\Events\UptimeCheckSucceeded as MonitorSucceededEvent;
use Spatie\UptimeMonitor\Models\Enums\UptimeStatus;
use Spatie\UptimeMonitor\Notifications\BaseNotification;

class UptimeCheckSucceeded extends BaseNotification
{
    public MonitorSucceededEvent $event;

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
            ->attachment(function (SlackAttachment $attachment) use ($title) {
                $attachment
                    ->title($title)
                    ->fallback($this->getMessageText())
                    ->footer($this->getLocationDescription())
                    ->timestamp(Carbon::now());
            });
    }

    public function isStillRelevant(): bool
    {
        return $this->getMonitor()->uptime_status != UptimeStatus::DOWN;
    }

    public function setEvent(MonitorSucceededEvent $event): self
    {
        $this->event = $event;

        return $this;
    }

    public function getMessageText(): string
    {
        return "Monitor {$this->getMonitor()->name} is up";
    }
}
