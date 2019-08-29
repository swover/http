<?php

namespace Swover\Http;

class Request
{
    public static function get($url, $params = [])
    {
        return self::send($url, 'GET', $params);
    }

    public static function post($url, $params = [])
    {
        return self::send($url, 'POST', $params);
    }

    /**
     * @param string $url
     * @param string $method
     * @param array $params
     * - config
     * - options
     * - headers
     * - proxy
     * - handler
     * @return mixed|Response
     */
    public static function send(string $url, $method, $params)
    {
        return Client::getClient($params)->request($method, $url, $params);
    }
}