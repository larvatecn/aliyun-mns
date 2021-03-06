<?php

namespace AliyunMNS\Responses;

use AliyunMNS\Exception\MnsException;
use Psr\Http\Message\StreamInterface;

/**
 * 响应基类
 */
abstract class BaseResponse
{
    /**
     * @var bool
     */
    protected $succeed;

    /**
     * @var int
     */
    protected $statusCode;

    /**
     * @param int $statusCode
     * @param StreamInterface $content
     * @return mixed
     */
    abstract public function parseResponse(int $statusCode, StreamInterface $content);

    /**
     * @return bool
     */
    public function isSucceed(): bool
    {
        return $this->succeed;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @param string $content
     * @return \XMLReader
     */
    protected function loadXmlContent(string $content): \XMLReader
    {
        $xmlReader = new \XMLReader();
        $isXml = $xmlReader->XML($content);
        if ($isXml === false) {
            throw new MnsException($this->statusCode, $content);
        }
        try {
            while ($xmlReader->read()) {
            }
        } catch (\Exception $e) {
            throw new MnsException($this->statusCode, $content);
        }
        $xmlReader->XML($content);
        return $xmlReader;
    }
}
