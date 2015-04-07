<?php

namespace LinguaLeo\EmarsysApiClient\Transport;

/**
 * Interface HttpTransportInterface
 * @package LinguaLeo\EmarsysApiClient\Transport
 */
interface HttpTransportInterface
{
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_DELETE = 'DELETE';

    /**
     * @param string $method
     * @param string $uri
     * @param string[] $headers
     * @param array $body
     * @return string
     */
    public function send($method, $uri, array $headers, array $body);
}
