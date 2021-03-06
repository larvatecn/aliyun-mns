<?php

namespace AliyunMNS\Responses;

use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Exception\TransferException;
use Psr\Http\Message\ResponseInterface;

class MnsPromise
{
    private $response;
    private $promise;

    public function __construct(PromiseInterface &$promise, BaseResponse &$response)
    {
        $this->promise = $promise;
        $this->response = $response;
    }

    /**
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->promise->getState() != 'pending';
    }

    /**
     * @return BaseResponse
     */
    public function getResponse(): BaseResponse
    {
        return $this->response;
    }

    /**
     * @return BaseResponse
     */
    public function wait()
    {
        try {
            $res = $this->promise->wait();
            if ($res instanceof ResponseInterface) {
                $this->response->parseResponse($res->getStatusCode(), $res->getBody());
            }
        } catch (TransferException $e) {
            $message = $e->getMessage();
            if ($e->hasResponse()) {
                $message = $e->getResponse()->getBody();
            }
            $this->response->parseErrorResponse($e->getCode(), $message);
        }
        return $this->response;
    }
}
