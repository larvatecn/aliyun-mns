<?php

namespace AliyunMNS\Exception;

class MnsException extends \RuntimeException
{
    private $mnsErrorCode;
    private $requestId;
    private $hostId;

    /**
     * MnsException constructor.
     * @param int $code
     * @param string $message
     * @param null $previousException
     * @param string|null $mnsErrorCode
     * @param string|null $requestId
     * @param string|null $hostId
     */
    public function __construct(int $code, string $message, $previousException = null, $mnsErrorCode = null, $requestId = null, $hostId = null)
    {
        parent::__construct($message, $code, $previousException);
        if ($mnsErrorCode == null) {
            if ($code >= 500) {
                $mnsErrorCode = "ServerError";
            } else {
                $mnsErrorCode = "ClientError";
            }
        }
        $this->mnsErrorCode = $mnsErrorCode;

        $this->requestId = $requestId;
        $this->hostId = $hostId;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        $str = "Code: " . $this->getCode() . " Message: " . $this->getMessage();
        if ($this->mnsErrorCode != NULL) {
            $str .= " MnsErrorCode: " . $this->mnsErrorCode;
        }
        if ($this->requestId != NULL) {
            $str .= " RequestId: " . $this->requestId;
        }
        if ($this->hostId != NULL) {
            $str .= " HostId: " . $this->hostId;
        }
        return $str;
    }

    public function getMnsErrorCode()
    {
        return $this->mnsErrorCode;
    }

    public function getRequestId()
    {
        return $this->requestId;
    }

    public function getHostId()
    {
        return $this->hostId;
    }
}
