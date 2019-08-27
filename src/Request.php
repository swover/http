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

    public static function autoGet(string $url, $params = [], $callback = null, $errCallback = null)
    {
        return self::autoSend('GET', $url, $params, $callback, $errCallback);
    }

    public static function autoPost($url, $params = [], $callback = null, $errCallback = null)
    {
        return self::autoSend('POST', $url, $params, $callback, $errCallback);
    }

    /**
     * @param string $method
     * @param string $url
     * @param array $params
     * - auto_param
     * - back_data
     * - config
     * - options
     * - proxy
     * @param $callback
     * @param $errCallback
     * @return mixed|Response
     * @throws \Exception
     */
    private static function autoSend($method, $url, $params = [], $callback = null, $errCallback = null)
    {
        $auto_param = $params['auto_param'] ?? [];
        $retry_count = $auto_param['retry_count'] ?? 1;
        unset($params['auto_param']);

        $backData = $params['back_data'] ?? [];

        $message = '';
        for ($retry = 0; $retry < $retry_count; $retry++) {
            if (($auto_param['retry_sleep'] ?? 0) && $retry > 0) {
                \Swoole\Coroutine::sleep(intval($auto_param['retry_sleep']));
            }

            if ($retry > 0) {
                $params['proxy'] = $params['proxy'] ?? true;
            }

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