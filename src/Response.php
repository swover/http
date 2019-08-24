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

    public function __construct(\Swoole\Coroutine\Http\Client $cli = null)
    {
        if (!$cli) {
            $this->status = false;
            return false;
        }

        $this->errCode = $cli->errCode;
        $this->statusCode = $cli->statusCode;

        if ($cli->statusCode < 0) {
            $this->status = false;
            $this->body = " Time Out [{$cli->statusCode}]. ";
        }

        if ( $cli->errCode > 0 ) {
            $this->status = false;
            $this->body .= socket_strerror($this->errCode);
        }

        if ($this->status == true) {
            $this->body = $cli->body;
        }

        $this->headers = $cli->headers;

        $this->cookies = $cli->cookies;

        if (isset($cli->url)) {
            $this->url = $cli->url;
        }

        return true;
    }

    public function setBody($message)
    {
        if ($this->status == true) {
            return false;
        }
        $this->body = $message;
        return true;
    }

    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    public function getHeader($header)
    {
        return $this->headers[$header]??null;
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
