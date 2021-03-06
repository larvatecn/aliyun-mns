<?php

namespace AliyunMNS\Signature;

use AliyunMNS\Requests\BaseRequest;
use AliyunMNS\Constants;

class Signature
{
    public static function SignRequest(string $accessKey, BaseRequest &$request): string
    {
        $headers = $request->getHeaders();
        $contentMd5 = "";
        if (isset($headers['Content-MD5'])) {
            $contentMd5 = $headers['Content-MD5'];
        }
        $contentType = "";
        if (isset($headers['Content-Type'])) {
            $contentType = $headers['Content-Type'];
        }
        $date = $headers['Date'];
        $queryString = $request->getQueryString();
        $canonicalizeResource = $request->getResourcePath();
        if ($queryString != NULL) {
            $canonicalizeResource .= "?" . $request->getQueryString();
        }
        if (0 !== strpos($canonicalizeResource, "/")) {
            $canonicalizeResource = "/" . $canonicalizeResource;
        }

        $tmpHeaders = array();
        foreach ($headers as $key => $value) {
            if (0 === strpos($key, Constants::MNS_HEADER_PREFIX)) {
                $tmpHeaders[$key] = $value;
            }
        }
        ksort($tmpHeaders);

        $canonicalizeMNSHeaders = implode("\n", array_map(function ($v, $k) {
            return $k . ":" . $v;
        }, $tmpHeaders, array_keys($tmpHeaders)));
        $stringToSign = strtoupper($request->getMethod()) . "\n" . $contentMd5 . "\n" . $contentType . "\n" . $date . "\n" . $canonicalizeMNSHeaders . "\n" . $canonicalizeResource;
        return base64_encode(hash_hmac("sha1", $stringToSign, $accessKey, $raw_output = true));
    }
}
