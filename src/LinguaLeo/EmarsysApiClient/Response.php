<?php

namespace LinguaLeo\EmarsysApiClient;

use LinguaLeo\EmarsysApiClient\Exceptions\ClientException;

/**
 * Class Response
 * @package LinguaLeo\EmarsysApiClient
 */
class Response
{
    const REPLY_CODE_OK = 0;
    const REPLY_CODE_INTERNAL_ERROR = 1;
    const REPLY_CODE_INVALID_KEY_FIELD = 2004;
    const REPLY_CODE_MISSING_KEY_FIELD = 2005;
    const REPLY_CODE_CONTACT_NOT_FOUND = 2008;
    const REPLY_CODE_CONTACT_ALREADY_EXISTS = 2009;
    const REPLY_CODE_NON_UNIQUE_RESULT = 2010;
    const REPLY_CODE_INVALID_STATUS = 6003;
    const REPLY_CODE_INVALID_DATA = 10001;

    /**
     * @var int
     */
    protected $replyCode;
    /**
     * @var string
     */
    protected $replyText;
    /**
     * @var array
     */
    protected $data = [];

    /**
     * @param array $result
     * @throws ClientException
     */
    function __construct(array $result = [])
    {
        if (!isset($result['replyCode']) || !isset($result['replyText']) || !isset($result['data'])) {
            throw new ClientException('Invalid result structure');
        }

        $this->replyCode = $result['replyCode'];
        $this->replyText = $result['replyText'];
        $this->data = $result['data'];
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return int
     */
    public function getReplyCode()
    {
        return $this->replyCode;
    }

    /**
     * @return string
     */
    public function getReplyText()
    {
        return $this->replyText;
    }
}
