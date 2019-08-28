<?php

namespace Swover\Http\Client;

use Swover\Http\Response;

class Guzzle extends BaseClient
{
    public function request($method, $url, $params)
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

        $response = $this->getResponse($result);
        $client = null;
        return $response;
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

    /**
     * @param $result \Psr\Http\Message\ResponseInterface|Response
     * @return Response
     */
    protected function getResponse($result)
    {

        $data = [
            'status' => true,
            'errCode' => $result->getErrCode(),
            'statusCode' => $result->getStatusCode(),
            'headers' => $result->getHeaders(),
            'cookies' => $result->getCookies(),
            'url' => $result->getUrl(),
        ];
        if ($data['statusCode'] < 0) {
            $data['status'] = false;
            $data['body'] = $client->errMsg ?? " Time Out [{$data['statusCode']}]. ";
        }

        if ($data['errCode'] > 0) {
            $data['status'] = false;
            $data['body'] .= function_exists('socket_strerror') ? socket_strerror($data['errCode']) : '';
        }

        if ($data['status'] == true) {
            $data['body'] = (string)$result->getBody();
        }

        return new Response($data);
    }
}