<?php

namespace Swover\Http\Client;

use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\TransferStats;

class Guzzle extends BaseClient
{
    public function request($method, $url, $params)
    {
        $params = $this->formatParams($params);

        $urlInfo = $this->parseUrl($url);

        $config = [
            //'base_uri'        => '',
            'timeout' => $this->timeout,
            'allow_redirects' => $this->allow_redirects,
            'verify' => false,
            'ssl.certificate_authority' => false,
        ];

        $client = new \GuzzleHttp\Client($config);

        $options = $this->buildOptions($params);
        /**
         * cookies
         */
        if (isset($params['cookies']) && is_array($params['cookies'])) {
            $options['cookies'] = CookieJar::fromArray($params['cookies'], $urlInfo['host']);
        }

        if ($urlInfo['scheme'] === 'https') {
            $options['ssl.certificate_authority'] = false;
            $options['verify'] = false;
        }

        $options['on_stats'] = function (TransferStats $stats) use (&$url) {
            $url = (string)$stats->getEffectiveUri();
        };

        try {
            $result = $client->request($method, $url, $options);

            return $this->response([
                'status' => true,
                'errCode' => 0,
                'statusCode' => $result->getStatusCode(),
                'headers' => $result->getHeaders(),
                'cookies' => (function () use ($result) {
                    $cookies = [];
                    foreach ($result->getHeader('Set-Cookie') as $cookString) {
                        $cookie = SetCookie::fromString($cookString);
                        $cookies[$cookie->getName()] = $cookie->getValue();
                    }
                    return $cookies;
                })(),
                'url' => $url,
                'body' => (string)$result->getBody()
            ]);

        } catch (\Throwable $e) {
            return $this->response([
                'status' => false,
                'errCode' => $e->getCode(),
                'statusCode' => -1,
                'body' => (string)$e->getMessage()
            ]);
        } finally {
            $client = null;
        }
    }

    private function buildOptions($params = [])
    {
        $options = [];

        /**
         * proxy
         */
        $proxy = $this->getProxy($params['proxy'] ?? false);
        if (($proxy['host'] ?? false) && ($proxy['host'] ?? false)) {
            $scheme = $proxy['scheme'] ?? 'http';
            $options['proxy'] = $scheme . '://';
            if ($proxy['user'] ?? false) {
                $options['proxy'] .= $proxy['user'] . ':' . ($proxy['pass'] ?? '') . '@';
            }
            //http://username:password@192.168.16.1:10
            $options['proxy'] .= $proxy['host'] . ':' . $proxy['port'];
        }

        /**
         * form data
         */
        if (isset($params['body'])) {
            $options['body'] = $params['body'];
        }
        if (isset($params['json'])) {
            $options['json'] = $params['json'];
        }
        if (isset($params['form_params'])) {
            $options['form_params'] = $params['form_params'];
        }
        if (isset($params['multipart'])) {
            $options['multipart'] = $params['multipart'];
        }

        /**
         * headers
         */
        $options['headers'] = $this->buildHeaders($params);

        return $options;
    }

    public function buildHeaders($params)
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
}