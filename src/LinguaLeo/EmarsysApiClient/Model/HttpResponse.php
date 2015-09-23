<?php

namespace LinguaLeo\EmarsysApiClient\Model;

class HttpResponse
{
    /** @var string */
    protected $code;

    /** @var string */
    protected $body;

    /** @var array  */
    protected $headers = [];

    /**
     * HttpResponse constructor.
     * @param string $body
     * @param int $code
     * @param array $headers
     */
    public function __construct($body, $code = 0, $headers = [])
    {
        $this->code = $code;
        $this->body = $body;
        $this->headers = $headers;
    }

    /**
     * @return int
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }
}
