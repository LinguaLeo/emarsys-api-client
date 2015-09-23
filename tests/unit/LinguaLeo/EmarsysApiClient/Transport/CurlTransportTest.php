<?php

namespace LinguaLeo\EmarsysApiClient\Transport;

/**
 * @covers \LinguaLeo\EmarsysApiClient\Transport\CurlTransport
 */
class CurlTransportTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CurlTransport
     */
    private $client;

    protected function setUp()
    {
        $this->client = new CurlTransport();
    }

    /**
     * @expectedException \LinguaLeo\EmarsysApiClient\Exceptions\ClientException
     */
    public function testRequestToNonExistingHostFails()
    {
        $this->client->send('POST', 'http://foo.bar');
    }

    public function testRequestReturnsOutput()
    {
        $result = $this->client->send('GET', 'http://google.com', [], ['foo' => 'bar']);

        $this->assertContains('<HTML', $result->getBody());
        $this->assertInternalType('integer', $result->getCode());
        $this->assertEquals([], $result->getHeaders());
    }
}
