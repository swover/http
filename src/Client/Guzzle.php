<?php

namespace Swover\Http\Client;

class Guzzle extends BaseClient
{
    public function request($method, $url, $params, $jump_number = 0)
    {
        $params = $this->keyToLower($params);

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

        /**
         * proxy
         */
        if (isset($params['proxy']) && is_array($params['proxy']) && !empty($params['proxy'])) {
            $options['proxy'] = "http://username:password@192.168.16.1:10"; //TODO
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