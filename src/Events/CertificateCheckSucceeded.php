<?php

namespace Spatie\UptimeMonitor\Events;

use Spatie\SslCertificate\SslCertificate;
use Spatie\UptimeMonitor\Models\Monitor;

class CertificateCheckSucceeded extends BaseEvent
{
    public SslCertificate $certificate;

    public function __construct(Monitor $monitor, SslCertificate $certificate)
    {
        parent::__construct($monitor);
        $this->certificate = $certificate;
    }
}
