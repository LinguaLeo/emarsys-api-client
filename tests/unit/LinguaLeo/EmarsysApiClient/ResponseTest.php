<?php

namespace LinguaLeo\EmarsysApiClient;

/**
 * @covers \LinguaLeo\Emarsys\Response
 */
class ResponseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \LinguaLeo\EmarsysApiClient\Exception\ClientException
     * @expectedExceptionMessage Invalid result structure
     */
    public function testItThrowsClientException()
    {
        $dummyResult = ['dummy'];
        new Response($dummyResult);
    }

    public function testItGetsResponseData()
    {
        $expectedResponse = $this->createExpectedResponse('createContact');
        $result = new Response($expectedResponse);

        $this->assertInternalType('array', $result->getData());
        $this->assertNotEmpty($result);

    }

    public function testItSetsAndGetsReplyCode()
    {
        $expectedResponse = $this->createExpectedResponse('createContact');
        $result = new Response($expectedResponse);

        $this->assertSame(Response::REPLY_CODE_OK, $result->getReplyCode());
    }

    public function testItSetsAndGetsReplyText()
    {
        $expectedResponse = $this->createExpectedResponse('createContact');
        $result = new Response($expectedResponse);

        $this->assertEquals('OK', $result->getReplyText());
    }

    /**
     * @param string $fileName
     * @return mixed
     */
    private function createExpectedResponse($fileName)
    {
        $fileContent = file_get_contents(__DIR__ . '/TestData/' . $fileName . '.json');

        return json_decode($fileContent, true);
    }
}
