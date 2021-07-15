<?php

namespace Spatie\UptimeMonitor\Events;

use Spatie\SslCertificate\SslCertificate;
use Spatie\UptimeMonitor\Models\Monitor;

class CertificateCheckFailed extends BaseEvent
{
    /** @var string */
    public string $reason;

    public ?SslCertificate $certificate;

    public function __construct(Monitor $monitor, string $reason, SslCertificate $certificate = null)
    {
        parent::__construct($monitor);
        $this->reason = $reason;
        $this->certificate = $certificate;
    }
}
