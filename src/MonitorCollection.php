<?php

namespace Spatie\UptimeMonitor;

use Acamposm\Ping\Ping;
use Acamposm\Ping\PingCommandBuilder;
use Generator;
use GrahamCampbell\GuzzleFactory\GuzzleFactory;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Promise\EachPromise;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\TransferStats;
use Illuminate\Support\Collection;
use Psr\Http\Message\ResponseInterface;
use Spatie\UptimeMonitor\Helpers\ConsoleOutput;
use Spatie\UptimeMonitor\Models\Monitor;
use Spatie\UptimeMonitor\Responses\PingResponse;

class MonitorCollection extends Collection
{
    public function checkUptime(): void
    {
        $this->resetItemKeys();

        (new EachPromise($this->getPromises(), [
            'concurrency' => config('uptime-monitor.uptime_check.concurrent_checks'),
            'fulfilled'   => function (ResponseInterface $response, $index) {
                $monitor = $this->getMonitorAtIndex($index);
                ConsoleOutput::info("Could reach {$monitor->url}");

                $monitor->uptimeRequestSucceeded($response);
            },

            'rejected' => function (TransferException $exception, $index) {
                $monitor = $this->getMonitorAtIndex($index);

                ConsoleOutput::error("Could not reach {$monitor->url} error: `{$exception->getMessage()}`");

                $monitor->uptimeRequestFailed($exception->getMessage());
            },
        ]))->promise()->wait();
    }

    protected function getPromises(): Generator
    {
        $client = GuzzleFactory::make(
            config('uptime-monitor.uptime_check.guzzle_options', []),
            config('uptime-monitor.uptime-check.retry_connection_after_milliseconds', 100)
        );

        foreach ($this->items as $monitor) {
            ConsoleOutput::info("Checking {$monitor->url}");

            if ($monitor->uptime_check_method === "ping") {
                $command = (new PingCommandBuilder($monitor->url))->count(config('uptime-monitor.uptime_check.ping_count', 1));
                try {
                    $ping           = (new Ping($command))->run();
                    $guzzle_options = config("uptime-monitor.uptime_check.guzzle_options", []);
                    $raw            = (array)$ping->raw;
                    $raw            = array_filter($raw, 'strlen');

                    if ($up = $ping->host_status === "Ok") {
                        $response = PingResponse::make(200, json_encode($ping));
                    } else {
                        $response  = PingResponse::make(504, json_encode($ping));
                        $exception = PingResponse::exception(end($raw), $monitor->url, json_encode($ping));
                    }

                    if (array_key_exists("on_stats", $guzzle_options)) {
                        $on_starts     = $guzzle_options["on_stats"];
                        $transfer_stat = new TransferStats(new Request("PING", $monitor->url, ["Monitor-Id" => $monitor->id]), $response, $ping->time->time);
                        $on_starts[0]::{$on_starts[1]}($transfer_stat);
                    }
                    if ($up) {
                        ConsoleOutput::info("Could reach {$monitor->url}");
                        $monitor->uptimeRequestSucceeded($response);
                    } else {
                        ConsoleOutput::error("Could not reach {$monitor->url} error Unreachable");
                        $monitor->uptimeRequestFailed($exception->getMessage());
                    }
                } catch (\Exception $exception) {
                    ConsoleOutput::error("Could not reach {$monitor->url} error " . $exception->getMessage());
                    $monitor->uptimeRequestFailed($exception->getMessage());
                }
                continue;
            } else {
                $promise = $client->requestAsync(
                    $monitor->uptime_check_method,
                    $monitor->url,
                    array_filter([
                        'connect_timeout' => config('uptime-monitor.uptime_check.timeout_per_site'),
                        'headers'         => $this->promiseHeaders($monitor),
                        'body'            => $monitor->uptime_check_payload,
                    ])
                );
            }
            yield $promise;
        }
    }

    private function promiseHeaders(Monitor $monitor): array
    {
        return collect([])
            ->merge(['User-Agent' => config('uptime-monitor.uptime_check.user_agent')])
            ->merge(config('uptime-monitor.uptime_check.additional_headers') ?? [])
            ->merge($monitor->uptime_check_additional_headers)
            ->toArray();
    }

    /**
     * In order to make use of Guzzle promises we have to make sure the
     * keys of the collection are in a consecutive order without gaps.
     */
    protected function resetItemKeys(): void
    {
        $this->items = $this->values()->all();
    }

    protected function getMonitorAtIndex(int $index): Monitor
    {
        return $this->items[$index];
    }

    public function sortByHost(): self
    {
        return $this->sortBy(function (Monitor $monitor) {
            return $monitor->url->getHost();
        });
    }
}
