<?php

namespace Spatie\UptimeMonitor\Notifications\Notifications;

use Carbon\Carbon;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackAttachment;
use Illuminate\Notifications\Messages\SlackMessage;
use Spatie\UptimeMonitor\Events\CertificateCheckSucceeded as ValidCertificateFoundEvent;
use Spatie\UptimeMonitor\Notifications\BaseNotification;

class CertificateCheckSucceeded extends BaseNotification
{
    public ValidCertificateFoundEvent $event;

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $mailMessage = (new MailMessage)
            ->subject($this->getMessageText())
            ->line($this->getMessageText());

        foreach ($this->getMonitorProperties() as $name => $value) {
            $mailMessage->line($name . ': ' . $value);
        }

        return $mailMessage;
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
                    ->content("Expires {$this->getMonitor()->formattedCertificateExpirationDate('forHumans')}")
                    ->fallback($this->getMessageText())
                    ->footer($this->getMonitor()->certificate_issuer)
                    ->timestamp(Carbon::now());
            });
    }

    public function setEvent(ValidCertificateFoundEvent $event): self
    {
        $this->event = $event;

        return $this;
    }

    public function getMessageText(): string
    {
        return "SSL certificate for {$this->event->monitor->name} is valid";
    }
}
