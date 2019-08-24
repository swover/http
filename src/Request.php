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

    public static function autoGet(string $url, $params = [], $backData = [], $callback = null, $errCallback = null)
    {
        return self::autoSend('GET', $url, $params, $backData, $callback, $errCallback);
    }

    public static function autoPost($url, $params = [], $backData = [], $callback = null, $errCallback = null)
    {
        return self::autoSend('POST', $url, $params, $backData, $callback, $errCallback);
    }

    private static function autoSend($method, $url, $params = [], $backData = [], $callback = null, $errCallback = null)
    {
        $auto_param = $params['auto_param'] ?? [];
        $retry_count = $auto_param['retry_count'] ?? 1;
        unset($params['auto_param']);

        $message = '';
        for ($retry = 0; $retry < $retry_count; $retry++) {
            if (($auto_param['retry_sleep'] ?? 0) && $retry > 0) {
                \Swoole\Coroutine::sleep(intval($auto_param['retry_sleep']));
            }

            $params['retry'] = $retry;

            try {
                $response = self::send($url, $method, $params);
                if (!$response->getStatus()) {
                    $message = $url . '请求异常，statusCode: ' . $response->getStatusCode() . ' errCode:' . $response->getErrCode() . ' error:' . $response->getBody();
                    continue;
                }

                if (!is_callable($callback)) {
                    return $response;
                }

                return call_user_func_array($callback, [$response, $backData]);
            } catch (\Exception $e) {
                $message = $e->getMessage();
            }
        }

        if (is_callable($errCallback)) {
            return call_user_func_array($errCallback, [$response ?? new Response(), $backData]);
        }

        throw new \Exception($message);
    }

    public static function send($url, $method, $params)
    {
        $config = [];
        if (isset($params['allow_redirects'])) {
            $config['allow_redirects'] = $params['allow_redirects'];
        }
        if (isset($params['timeout'])) {
            $config['timeout'] = $params['timeout'];
        }

        return Client::getClient($config)->request($method, $url, $params);
    }
}