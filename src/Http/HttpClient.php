<?php
namespace AliyunMNS\Http;

use AliyunMNS\Config;
use AliyunMNS\Constants;
use AliyunMNS\Exception\MnsException;
use AliyunMNS\Requests\BaseRequest;
use AliyunMNS\Responses\BaseResponse;
use AliyunMNS\Signature\Signature;
use AliyunMNS\AsyncCallback;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\TransferException;
use AliyunMNS\Responses\MnsPromise;

class HttpClient
{
    /**
     * @var \GuzzleHttp\Client
     */
    private $client;

    /**
     * @var string
     */
    private $region;

    /**
     * @var string
     */
    private $accountId;

    /**
     * @var string
     */
    private $accessId;

    /**
     * @var string
     */
    private $accessKey;

    /**
     * @var string|null
     */
    private $securityToken;

    private $requestTimeout;

    private $connectTimeout;

    /**
     * @var string
     */
    private $endpoint;

    /**
     * HttpClient constructor.
     * @param string $endPoint
     * @param string $accessId
     * @param string $accessKey
     * @param string|null $securityToken
     * @param Config|null $config
     */
    public function __construct(string $endPoint, string $accessId, string $accessKey, $securityToken = null, Config $config = null)
    {
        if ($config == null) {
            $config = new Config;
        }
        $this->accessId = $accessId;
        $this->accessKey = $accessKey;
        $this->client = new \GuzzleHttp\Client([
            'base_uri' => $endPoint,
            'defaults' => [
                'headers' => [
                    'Host' => $endPoint
                ],
                'proxy' => $config->getProxy(),
                'expect' => $config->getExpectContinue()
            ]
        ]);
        $this->requestTimeout = $config->getRequestTimeout();
        $this->connectTimeout = $config->getConnectTimeout();
        $this->securityToken = $securityToken;
        $this->endpoint = $endPoint;
        $this->parseEndpoint();
    }

    public function getRegion()
    {
        return $this->region;
    }

    public function getAccountId()
    {
        return $this->accountId;
    }

    /**
     * ???????????????
     */
    private function parseEndpoint()
    {
        $pieces = explode("//", $this->endpoint);
        $host = end($pieces);

        $host_pieces = explode(".", $host);
        $this->accountId = $host_pieces[0];
        $region_pieces = explode("-internal", $host_pieces[2]);
        $this->region = $region_pieces[0];
    }

    /**
     * @param BaseRequest $request
     */
    private function addRequiredHeaders(BaseRequest &$request)
    {
        $body = $request->generateBody();
        $queryString = $request->generateQueryString();

        $request->setBody($body);
        $request->setQueryString($queryString);

        if ($body != NULL) {
            $request->setHeader(Constants::CONTENT_LENGTH, strlen($body));
        }
        $request->setHeader('Date', gmdate(Constants::GMT_DATE_FORMAT));
        if (!$request->isHeaderSet(Constants::CONTENT_TYPE)) {
            $request->setHeader(Constants::CONTENT_TYPE, 'text/xml');
        }
        $request->setHeader(Constants::MNS_VERSION_HEADER, Constants::MNS_VERSION);

        if ($this->securityToken != NULL) {
            $request->setHeader(Constants::SECURITY_TOKEN, $this->securityToken);
        }

        $sign = Signature::SignRequest($this->accessKey, $request);
        $request->setHeader(Constants::AUTHORIZATION,
            Constants::MNS . " " . $this->accessId . ":" . $sign);
    }

    /**
     * @param BaseRequest $request
     * @param BaseResponse $response
     * @param AsyncCallback|NULL $callback
     * @return MnsPromise
     */
    public function sendRequestAsync(BaseRequest $request, BaseResponse &$response, AsyncCallback $callback = NULL)
    {
        $promise = $this->sendRequestAsyncInternal($request, $response, $callback);
        return new MnsPromise($promise, $response);
    }

    /**
     * @param BaseRequest $request
     * @param BaseResponse $response
     * @return BaseResponse
     */
    public function sendRequest(BaseRequest $request, BaseResponse &$response)
    {
        $promise = $this->sendRequestAsync($request, $response);
        return $promise->wait();
    }

    /**
     * @param BaseRequest $request
     * @param BaseResponse $response
     * @param AsyncCallback|NULL $callback
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    private function sendRequestAsyncInternal(BaseRequest &$request, BaseResponse &$response, AsyncCallback $callback = NULL)
    {
        $this->addRequiredHeaders($request);

        $parameters = array('exceptions' => false, 'http_errors' => false);
        $queryString = $request->getQueryString();
        $body = $request->getBody();
        if ($queryString != NULL) {
            $parameters['query'] = $queryString;
        }
        if ($body != NULL) {
            $parameters['body'] = $body;
        }

        $parameters['timeout'] = $this->requestTimeout;
        $parameters['connect_timeout'] = $this->connectTimeout;

        $request = new Request(strtoupper($request->getMethod()), $request->getResourcePath(), $request->getHeaders());
        try {
            if ($callback != null) {
                return $this->client->sendAsync($request, $parameters)->then(
                    function (ResponseInterface $res) use (&$response, $callback) {
                        try {
                            $response->parseResponse($res->getStatusCode(), $res->getBody());
                            $callback->onSucceed($response);
                        } catch (MnsException $e) {
                            $callback->onFailed($e);
                        }
                    }
                );
            } else {
                return $this->client->sendAsync($request, $parameters);
            }
        } catch (TransferException $e) {
            $message = $e->getMessage();
            if ($e->hasResponse()) {
                $message = $e->getResponse()->getBody();
            }
            throw new MnsException($e->getCode(), $message, $e);
        }
    }
}
