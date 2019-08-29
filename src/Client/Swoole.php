<?php

namespace Swover\Http\Client;

use Swoole\Coroutine\Http\Client;

class Swoole extends BaseClient
{
    protected $jump_number = 0;

    public function request($method, $url, $params)
    {
        $params = $this->formatParams($params);

        $urlInfo = $this->parseUrl($url);

        $client = new Client($urlInfo['host'], $urlInfo['port'], $urlInfo['schema'] === 'https' ? true : null);

        $options = array_merge($params['options'] ?? [], $this->buildOptions($params));

        $headers = $this->buildHeaders($params);
        $headers['host'] = $headers['host'] ?? $urlInfo['host'];
        $client->setHeaders(array_merge($options['headers'], $headers));

        $client->set(array_merge($options['setting'], $this->buildSetting($params)));

        if (isset($options['cookies']) && is_array($options['cookies'])) {
            $client->setCookies($options['cookies']);
            unset($options['cookies']);
        }

        $client->setMethod($method);
        if (isset($options['json'])) {
            $client->setData($options['json']);
        }

        $path = $urlInfo['path'] . $urlInfo['query'] ? ('?' . $urlInfo['query']) : '';
        if ($method == 'POST' && isset($options['form_params'])) {
            $client->post($path, $options['form_params'] ?? []);
        } else {
            $client->execute($path);
        }

        if ($this->allow_redirects) {
            if ($client->statusCode == 302 || $client->statusCode == 301
                || (isset($client->headers['location']) && strlen($client->headers['location']) > 0)) {
                if ($this->jump_number <= $this->max_jump) {
                    $url = $client->headers['location'];
                    $client->close();
                    $client = null;
                    $this->jump_number++;
                    return $this->request($method, $url, $params);
                }
            }
        }

        $response = $this->response([
            'status' => true,
            'errCode' => $client->errCode,
            'statusCode' => $client->statusCode,
            'headers' => $client->headers,
            'cookies' => $client->cookies,
            'url' => $url,
            'body' => $client->body
        ]);

        $client->close();
        return $response;
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

        $params['proxy'] = $params['proxy'] ?? false;
        if ($params['proxy']) {
            if (is_array($params['proxy'])) {
            } elseif (is_bool($params['proxy'])) {
                //TODO
            } else {
                //TODO
            }

            if (($params['proxy']['host'] ?? false)
                && ($params['proxy']['host'] ?? false)) {
                $schema = $params['proxy']['schema'] ?? 'http';
                if ($schema == 'http') {
                    $result['http_proxy_host'] = $params['proxy']['host'];
                    $result['http_proxy_port'] = $params['proxy']['port'];
                    $result['http_proxy_user'] = $params['proxy']['user'] ?? '';
                    $result['http_proxy_password'] = $params['proxy']['password'] ?? '';
                }
                if ($schema == 'socks5') {
                    $result['socks5_host'] = $params['proxy']['host'];
                    $result['socks5_port'] = $params['proxy']['port'];
                    $result['socks5_username'] = $params['proxy']['user'] ?? '';
                    $result['socks5_password'] = $params['proxy']['password'] ?? '';
                }
            }
        }

        return $setting;
    }

    private function buildOptions($params)
    {
        $options = [];

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