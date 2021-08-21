<?php

namespace Spatie\UptimeMonitor\Notifications\Notifications;

use Carbon\Carbon;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackAttachment;
use Illuminate\Notifications\Messages\SlackMessage;
use Spatie\UptimeMonitor\Events\CertificateExpiresSoon as SoonExpiringSslCertificateFoundEvent;
use Spatie\UptimeMonitor\Notifications\BaseNotification;

class CertificateExpiresSoon extends BaseNotification
{
    public SoonExpiringSslCertificateFoundEvent $event;

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $mailMessage = (new MailMessage)
            ->error()
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
            ->warning()
            ->attachment(function (SlackAttachment $attachment) use ($title) {
                $attachment
                    ->title($title)
                    ->content("Expires {$this->getMonitor()->formattedCertificateExpirationDate('forHumans')}")
                    ->fallback($this->getMessageText())
                    ->footer($this->getMonitor()->certificate_issuer)
                    ->timestamp(Carbon::now());
            });
    }

    public function setEvent(SoonExpiringSslCertificateFoundEvent $event)
    {
        $this->event = $event;

        return $this;
    }

    protected function getMessageText(): string
    {
        return "SSL certificate for {$this->getMonitor()->name} expires soon";
    }
}
