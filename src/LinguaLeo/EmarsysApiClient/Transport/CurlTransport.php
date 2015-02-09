<?php

namespace LinguaLeo\EmarsysApiClient\Transport;

use LinguaLeo\EmarsysApiClient\Exceptions\ClientException;

class CurlTransport implements HttpTransportInterface
{
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

        curl_setopt($ch, CURLOPT_URL, $uri);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $output = curl_exec($ch);

        curl_close($ch);

        if (false == $output) {
            throw new ClientException();
        }

        return $output;
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