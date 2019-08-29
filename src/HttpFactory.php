<?php

namespace Swover\Http;

use Swover\Http\Client\BaseClient;

class HttpFactory
{
    /**
     * @var BaseClient|bool
     */
    protected static $handler = false;

    protected static $proxy = null;

    public static function setHandler($handler)
    {
        if (is_object($handler) && $handler instanceof BaseClient) {
            return self::$handler = $handler; //get_class($handler);
        }

        if ($handler == 'curl') return self::$handler = '\Swover\Http\Client\Curl';

        if ($handler == 'guzzle') return self::$handler = '\Swover\Http\Client\Guzzle';

        return self::$handler = false;
    }

    public static function getHandler($default = false)
    {
        if ($default !== false) return $default;

        if (self::$handler !== false) return self::$handler;

        if (PHP_SAPI == 'cli'
            && class_exists('\Swoole\Coroutine')
            && call_user_func('\Swoole\Coroutine::getCid') > 0) {
            return '\Swover\Http\Client\Swoole';
        }

        if (class_exists('\GuzzleHttp\Client')) return '\Swover\Http\Client\Guzzle';

        if (extension_loaded('curl')) return '\Swover\Http\Client\Curl';

        return '\Swover\Http\Client\Stream';
    }

    public static function setProxy($handler)
    {
        self::$proxy = $handler;
    }

    public static function getProxy($handler)
    {
        return self::$proxy;
    }
}