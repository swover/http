<?php

namespace Swover\Http\Client;

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

        if ($urlInfo['schema'] === 'https') {
            $options['ssl.certificate_authority'] = false;
            $options['verify'] = false;
        }
        //$options['cookies'] = CookieJar::fromArray($params['cookieJar'], $params['cookieUrl']);

        $result = $client->request($method, $url, $options);

        $response = $this->response([
            'status' => true,
            'errCode' => $result->getErrCode(),
            'statusCode' => $result->getStatusCode(),
            'headers' => $result->getHeaders(),
            'cookies' => $result->getCookies(),
            'url' => $result->getUrl(),
            'body' => (string)$result->getBody()
        ]);

        $client = null;
        return $response;
    }

    private function buildOptions($params = [])
    {
        $options = [];

        /**
         * proxy
         */
        $proxy = $this->getProxy($params['proxy'] ?? false);
        if (($proxy['host'] ?? false) && ($proxy['host'] ?? false)) {
            $schema = $proxy['schema'] ?? 'http';
            $options['proxy'] = $schema . '://';
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