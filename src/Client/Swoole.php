<?php

namespace Swover\Http\Client;

use Swover\Http\Proxy;
use Swover\Http\Response;
use Swoole\Coroutine\Http\Client;

class Swoole extends BaseClient
{
    public function request($method, $url, $params, $jump_number = 0)
    {
        $request = $this->filterUrl($url);
        if (empty($request['domain'])) {
            throw new \Exception('Has Not domain!');
        }
        $client = new Client($request['domain'], $request['port'], $request['https'] ? true : null);

        $options = $this->buildOptions($params);

        if (!isset($options['headers']['Host'])) {
            $options['headers']['Host'] = $request['domain'];
        }
        if (isset($options['headers']['cookie']) && is_array($options['headers']['cookie'])) {
            $client->setCookies($options['headers']['cookie']);
            unset($options['headers']['cookie']);
        }
        $client->setHeaders($options['headers']);

        $client->set($options['setting']);

        $client->setMethod($method);

        if (isset($options['json'])) {
            $client->setData($options['json']);
        }

        if ($method == 'POST') {
            if (isset($options['form_params'])) {
                $client->post($request['path'], $options['form_params'] ?? []);
            } else {
                $client->execute($request['path']);
            }
        } else {
            $client->get($request['path']);
        }

        if ($this->allow_redirects) {
            if ($client->statusCode == 302 || $client->statusCode == 301
                || (isset($client->headers['location']) && mb_strlen($client->headers['location']) > 0)) {
                if ($jump_number <= $this->max_jump) {
                    $url = $client->headers['location'];
                    $client->close();
                    $client = null;
                    return $this->request($method, $url, $params, ($jump_number + 1));
                }
            }
        }

        $client->url = $url;

        $response = new Response($client);

        $client->close();
        return $response;
    }

    private function buildHeaders($params)
    {
        $headers = isset($params['headers']) ? $params['headers'] : [];

        if (!isset($headers['User-Agent'])) {
            if (isset($params['mobile_agent']) && $params['mobile_agent'] === true) {
                $headers['User-Agent'] = $this->randomMobileAgent();
            } else {
                $headers['User-Agent'] = $this->randUserAgent();
            }
        }

        if (isset($params['cookie'])) {
            if (!isset($headers['cookie']) || empty($headers['cookie'])) {
                $headers['cookie'] = $params['cookie'];
            }
        }

        return $headers;
    }

    private function buildSetting($params)
    {
        $setting = [
            'timeout' => $this->timeout,
            'ssl_verify_peer' => false,
        ];

        if (!isset($params['proxy_setting']) || empty($params['proxy_setting'])) {
            if ((isset($params['proxy']) && $params['proxy'] === true)
                || (isset($params['retry']) && intval($params['retry']) > 0)) {
                $proxy = Proxy::get();
                if (isset($proxy['host']) && isset($proxy['port'])) {
                    $params['proxy_setting'] = $proxy;
                }
            }
        }

        if (isset($params['proxy_setting'])) {
            $params['proxy_setting'] = $this->buildHttpProxy($params['proxy_setting']);
            $setting += $params['proxy_setting'];
        }

        return $setting;
    }

    private function buildHttpProxy($proxy)
    {
        $result = [
            'http_proxy_host' => $proxy['host'] ?? ($proxy['http_proxy_host'] ?? ''),
            'http_proxy_port' => $proxy['port'] ?? ($proxy['http_proxy_port'] ?? ''),
            'http_proxy_user' => $proxy['user'] ?? ($proxy['http_proxy_user'] ?? ''),
            'http_proxy_password' => $proxy['password'] ?? ($proxy['http_proxy_password'] ?? ''),
        ];
        if (!$result['http_proxy_host'] || !$result['http_proxy_port']) {
            return [];
        }
        return $result;
    }

    private function buildOptions($params)
    {
        $options = [
            'headers' => $this->buildHeaders($params),
            'setting' => $this->buildSetting($params)
        ];

        if (isset($params['json'])) {
            if (is_array($params['json'])) {
                $params['json'] = json_encode($params['json'], JSON_UNESCAPED_UNICODE);
            }
            $options['json'] = $params['json'];
        }

        if (isset($params['form_params'])) {
            $options['form_params'] = $params['form_params'];
        }

        return $options;
    }

    private function filterUrl($url)
    {
        $result = [
            'https' => false,
            'domain' => '',
            'path' => '/',
            'port' => 80
        ];

        if (substr($url, 0, 8) == 'https://') {
            $result['https'] = true;
            $result['port'] = 443;
            $url = substr($url, 8);
        }

        if (substr($url, 0, 7) == 'http://') {
            $url = substr($url, 7);
        }

        $url = ltrim(ltrim(ltrim($url, ':'), '/'), ':');

        $point = strpos($url, '/');
        if ($point === false) {
            $point = strpos($url, '?');
            if ($point === false) {
                $result['domain'] = $url;
                $result['path'] = '/';
            } else {
                $result['domain'] = substr($url, 0, $point);
                $result['path'] = '/' . substr($url, $point);
            }
        } else {
            $result['domain'] = substr($url, 0, $point);
            $result['path'] = substr($url, $point);
        }

        $temp = explode(':', $result['domain']);
        if (count($temp) > 1) {
            $result['domain'] = $temp[0];
            $result['port'] = $temp[1];
        }

        return $result;
    }

    private static function fail($body = 'Undefined')
    {
        $response = new Response();
        $response->setBody($body);
        return $response;
    }
}