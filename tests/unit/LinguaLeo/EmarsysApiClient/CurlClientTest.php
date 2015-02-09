<?php

namespace LinguaLeo\EmarsysApiClient;

/**
 * @covers \LinguaLeo\Emarsys\CurlClient
 */
class CurlClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CurlClient
     */
    private $client;

    protected function setUp()
    {
        $this->client = new CurlClient();
    }

    /**
     * @expectedException \LinguaLeo\EmarsysApiClient\Exception\ClientException
     */
    public function testRequestToNonExistingHostFails()
    {
        $this->client->send('POST', 'http://foo.bar');
    }

    public function testRequestReturnsOutput()
    {
        $result = $this->client->send('GET', 'http://google.com', [], ['foo' => 'bar']);

        $this->assertContains('<HTML', $result);
    }
}
