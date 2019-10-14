<?php

namespace Swover\Http;

/**
 * @method static Response get($url, $params = [])
 * @method static Response post($url, $params = [])
 * @method static Response put($url, $params = [])
 * @method static Response delete($url, $params = [])
 * @method static Response patch($url, $params = [])
 * @method static Response head($url, $params = [])
 * @method static Response options($url, $params = [])
 */
class Request
{
    public static function __callStatic($name, $arguments)
    {
        if (in_array(strtolower($name), ['get', 'post', 'put', 'delete', 'patch', 'head', 'options'])) {
            return self::send($arguments[0] ?? '', strtoupper($name), $arguments[1] ?? []);
        }
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
        $client = HttpFactory::getHandler($params['handler'] ?? false);
        
        $instance = is_object($client) ? $client : (new $client($params));

        return $instance->request(strtoupper($method), $url, $params);
    }
}