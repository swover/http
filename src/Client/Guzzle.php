<?php

namespace Swover\Http\Client;

class Guzzle extends BaseClient
{
    public function request($method, $url, $params, $jump_number = 0)
    {
        $config = [
            //'base_uri'        => '',
            'timeout' => $this->timeout,
            'allow_redirects' => $this->allow_redirects,
            'verify' => false,
            'ssl.certificate_authority' => false,
        ];

        $client = new \GuzzleHttp\Client($config);

        $options = $this->buildOptions($params);

        $urlInfo = $this->parseUrl($url);

        if ($urlInfo['schema'] === 'https') {
            $options['ssl.certificate_authority'] = false;
            $options['verify'] = false;
        }
        //$options['cookies'] = CookieJar::fromArray($params['cookieJar'], $params['cookieUrl']);

        $result = $client->request($method, $url, $options);
        return $result;
    }

    private function buildOptions($params = [])
    {
        $options = [];

        if (isset($params['proxy']) && $params['proxy'] === true) {
            $options['proxy'] = "http://username:password@192.168.16.1:10"; //TODO
        }

        if (isset($params['form_params'])) {
            if (!is_array($params['form_params'])) {
                throw new \InvalidArgumentException('form params must array.');
            }
            $options['form_params'] = $params['form_params'];
        }

        if (isset($params['json'])) {
            if (!is_array($params['json'])) {
                throw new \InvalidArgumentException('json params must array.');
            }
            $options['json'] = $params['json'];
        }

        if (isset($params['multipart'])) {
            if (!is_array($params['multipart'])) {
                throw new \InvalidArgumentException('multipart params must array.');
            }
            $options['multipart'] = $params['multipart'];
        }

        if (!isset($params['headers'])) {
            $params['headers'] = [
                'user-agent' => $this->randUserAgent()
            ];
        }

        $options['headers'] = $params['headers'];
        if (!isset($params['headers']['user-agent'])) {
            $options['headers']['user-agent'] = $this->randUserAgent();
        }

        return $options;
    }
}