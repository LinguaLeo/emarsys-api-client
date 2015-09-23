<?php

namespace LinguaLeo\EmarsysApiClient\Transport;

use LinguaLeo\EmarsysApiClient\Model\HttpResponse;

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
     * @return HttpResponse
     */
    public function send($method, $uri, array $headers, array $body);

    /**
     * @param int $timeout
     * @return void
     */
    public function setTimeout($timeout);
}
