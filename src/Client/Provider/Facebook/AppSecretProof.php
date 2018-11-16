<?php

namespace Vairogs\Utils\Oauth\Client\Provider\Facebook;

class AppSecretProof
{
    /**
     * @see https://developers.facebook.com/docs/graph-api/securing-requests#appsecret_proof
     *
     * @param string $appSecret
     * @param string $accessToken
     *
     * @return string
     */
    public static function create($appSecret, $accessToken): string
    {
        return \hash_hmac('sha256', $accessToken, $appSecret);
    }
}
