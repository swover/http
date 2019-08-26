<?php

namespace Swover\Http;

class Response
{
    private $status = true;

    private $errCode = 0;

    private $statusCode = 200;

    private $headers = [];

    private $body = null;

    private $cookies = [];

    private $url = null;

    public function __construct($data = [])
    {
        if (empty($data)) {
            $this->status = false;
            return true;
        }

        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function getHeader($header)
    {
        return $this->headers[$header] ?? null;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getCookies()
    {
        return $this->cookies;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getErrCode()
    {
        return $this->errCode;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }
}
