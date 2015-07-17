<?php

namespace LinguaLeo\EmarsysApiClient\Transport;

use LinguaLeo\EmarsysApiClient\Exceptions\ClientException;

/**
 * Class CurlTransport
 * @package LinguaLeo\EmarsysApiClient\Transport
 */
class CurlTransport implements HttpTransportInterface
{
    /**
     * Connection timeout, seconds
     * @var int
     */
    protected $connectionTimeout = 60;

    /**
     * Operation timeout, seconds
     * @var int
     */
    protected $operationTimeout = 60;
    /**
     * @var array
     */
    protected $curlOpts = [];

    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        if (isset($options['timeout'])) {
            $this->setTimeout($options['timeout']);
        }

        if (isset($options['curl_opts'])) {
            $this->setCurlOpts($options['curl_opts']);
        }
    }

    /**
     * @param string $method
     * @param string $uri
     * @param string[] $headers
     * @param array $body
     * @return string
     * @throws ClientException
     */
    public function send($method, $uri, array $headers = [], array $body = [])
    {
        $ch = curl_init();
        $uri = $this->updateUri($method, $uri, $body);

        if ($method != self::METHOD_GET) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        if ($this->curlOpts && !curl_setopt_array($ch, $this->curlOpts)) {
            throw new ClientException('Error while setting CURL options');
        }

        curl_setopt($ch, CURLOPT_URL, $uri);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectionTimeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->operationTimeout);

        $output = curl_exec($ch);

        curl_close($ch);

        if (false == $output) {
            throw new ClientException('Operation timeout');
        }

        return $output;
    }

    /**
     * @param int $timeout
     * @return void
     */
    public function setTimeout($timeout)
    {
        $this->setOperationTimeout($timeout);
        $this->setConnectionTimeout($timeout);
    }


    /**
     * Set operation timeout, in seconds
     * @param int $timeout
     */
    protected function setOperationTimeout($timeout)
    {
        $this->operationTimeout = $timeout;
    }

    /**
     * Set connection timeout, in seconds
     * @param int $timeout
     */
    protected function setConnectionTimeout($timeout)
    {
        $this->connectionTimeout = $timeout;
    }

    /**
     * @param array $curlOpts
     */
    private function setCurlOpts(array $curlOpts)
    {
        $this->curlOpts = $curlOpts;
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array $body
     * @return string
     */
    private function updateUri($method, $uri, array $body)
    {
        if (self::METHOD_GET == $method) {
            $uri .= '/' . http_build_query($body);
        }

        return $uri;
    }
}
