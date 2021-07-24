<?php
/**
 *
 * Created by PhpStorm.
 * User: Hiệp Nguyễn
 * Filename: PingResponse.php
 * Date: 24/07/2021
 * Time: 10:36
 */

namespace Spatie\UptimeMonitor\Responses;


use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

class PingResponse
{
    /**
     * @param int $code
     * @param string $data
     * @return Response
     */
    public static function make(int $code, string $data)
    {
        return new Response($code, [], $data);
    }

    /**
     * @param string $message
     * @param string $uri
     * @param string $data
     * @return RequestException
     */
    public static function exception(string $message, string $uri, string $data)
    {
        return new RequestException($message, new Request("GET", $uri), self::make(500, $data));
    }
}
