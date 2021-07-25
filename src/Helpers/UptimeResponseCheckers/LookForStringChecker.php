<?php

namespace Spatie\UptimeMonitor\Helpers\UptimeResponseCheckers;

use Psr\Http\Message\ResponseInterface;
use Spatie\UptimeMonitor\Models\Monitor;

class LookForStringChecker implements UptimeResponseChecker
{
    public function isValidResponse(ResponseInterface $response, Monitor $monitor): bool
    {
        if (empty($monitor->look_for_string)) {
            return true;
        }

        return strpos($response->getBody()->getContents(), $monitor->look_for_string) !== false;
    }

    public function getFailureReason(ResponseInterface $response, Monitor $monitor): string
    {
        return "String `{$monitor->look_for_string}` was not found on the response.";
    }
}
