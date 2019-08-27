<?php

namespace Swover\Http;

use Swover\Http\Client\BaseClient;
use Swover\Http\Client\Curl;
use Swover\Http\Client\Guzzle;
use Swover\Http\Client\Stream;
use Swover\Http\Client\Swoole;

class Client
{
    protected static $handler = false;

    public static function getClient($params)
    {
        self::setHandler($params['handler'] ?? false); //TODO

        if (PHP_SAPI == 'cli'
            && class_exists('\Swoole\Coroutine')
            && \Swoole\Coroutine::getCid() > 0) {
            if (self::$handler === false) return new Swoole($params);
        }

        if (self::$handler === false || !class_exists(self::$handler)) {
            if (class_exists('\GuzzleHttp\Client')) return new Guzzle($params);

            if (extension_loaded('curl')) return new Curl($params);

            return new Stream($params);
        }

        return new (self::$handler)($params);
    }

    public static function setHandler($handler)
    {
        if (is_object($handler) && $handler instanceof BaseClient) {
            return self::$handler = get_class($handler);
        }

        if ($handler == 'curl') return self::$handler = '\Swover\Http\Client\Curl';

        if ($handler == 'guzzle') return self::$handler = '\Swover\Http\Client\Guzzle';

        return self::$handler = false;
    }
}