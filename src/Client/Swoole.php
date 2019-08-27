<?php

namespace Swover\Http\Client;

use Swover\Http\Proxy;
use Swover\Http\Response;
use Swoole\Coroutine\Http\Client;

class Swoole extends BaseClient
{
    protected $jump_number = 0;

    public function request($method, $url, $params)
    {
        $params = $this->keyToLower($params);

        $urlInfo = $this->parseUrl($url);

        $client = new Client($urlInfo['host'], $urlInfo['port'], $urlInfo['schema'] === 'https' ? true : null);

        $options = array_merge($params['options'] ?? [], $this->buildOptions($params));

        if (!isset($options['headers']['host'])) {
            $options['headers']['host'] = $urlInfo['host'];
        }

        if (isset($options['cookies']) && is_array($options['cookies'])) {
            $client->setCookies($options['cookies']);
            unset($options['cookies']);
        }

        $client->setHeaders($options['headers']);

        $client->set($options['setting']);

        $client->setMethod($method);

        if (isset($options['json'])) {
            $client->setData($options['json']);
        }

        $urlInfo['path'] .= $urlInfo['query'] ? ('?' . $urlInfo['query']) : '';
        if ($method == 'POST') {
            if (isset($options['form_params'])) {
                $client->post($urlInfo['path'], $options['form_params'] ?? []);
            } else {
                $client->execute($urlInfo['path']);
            }
        } else {
            $client->get($urlInfo['path']);
        }

        if ($this->allow_redirects) {
            if ($client->statusCode == 302 || $client->statusCode == 301
                || (isset($client->headers['location']) && mb_strlen($client->headers['location']) > 0)) {
                if ($this->jump_number <= $this->max_jump) {
                    $url = $client->headers['location'];
                    $client->close();
                    $client = null;
                    $this->jump_number++;
                    return $this->request($method, $url, $params);
                }
            }
        }

        $client->url = $url;

        $response = $this->getResponse($client);

        $client->close();
        return $response;
    }

    /**
     * @param $client
     * @return Response|void
     */
    protected function getResponse($client)
    {
        $data = [
            'status' => true,
            'errCode' => $client->errCode,
            'statusCode' => $client->statusCode,
            'headers' => $client->headers,
            'cookies' => $client->cookies,
            'url' => $client->url,
        ];
        if ($client->statusCode < 0) {
            $data['status'] = false;
            $data['body'] = $client->errMsg ?? " Time Out [{$client->statusCode}]. ";
        }

        if ($client->errCode > 0) {
            $data['status'] = false;
            $data['body'] .= function_exists('socket_strerror') ? socket_strerror($this->errCode) : '';
        }

        if ($data['status'] == true) {
            $data['body'] = $client->body;
        }

        return new Response($data);
    }

    private function buildHeaders($params)
    {
        $headers = isset($params['headers']) ? $params['headers'] : [];

        if (!isset($headers['user-agent'])) {
            if (boolval($params['mobile_agent'] ?? false) === true) {
                $headers['user-agent'] = $this->randomMobileAgent();
            } else {
                $headers['user-agent'] = $this->randUserAgent();
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

        /*if (isset($params['cookies']) && is_array($params['cookies'])) {
            $options['cookies'] = $params['cookies'];
        }*/

        if (isset($params['json'])) {
            if (is_array($params['json'])) {
                $params['json'] = call_user_func(function_exists('json_encode') ? 'json_encode' : 'http_build_query', $params['json']);
            }
            $options['json'] = $params['json'];
        }

        if (isset($params['form_params'])) {
            $options['form_params'] = $params['form_params'];
        }

        return $options;
    }
}