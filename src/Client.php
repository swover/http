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

    public static function getClient($config)
    {
        if (PHP_SAPI == 'cli'
            && class_exists('\Swoole\Coroutine')
            && \Swoole\Coroutine::getCid() > 0) {
            if (self::$handler === false) return new Swoole($config);
        }

        if (self::$handler === false || !class_exists(self::$handler)) {
            if (class_exists('\GuzzleHttp\Client')) return new Guzzle($config);

            if (extension_loaded('curl')) return new Curl($config);

            return new Stream($config);
        }

        return new (self::$handler)($config);
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