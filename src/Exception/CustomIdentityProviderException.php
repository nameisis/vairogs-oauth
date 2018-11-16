<?php

namespace Vairogs\Utils\Oauth\Exception;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Psr\Http\Message\ResponseInterface;

class CustomIdentityProviderException extends IdentityProviderException
{
    public static function clientException(ResponseInterface $response, $data)
    {
        return static::fromResponse($response, $data['message'] ?? $response->getReasonPhrase());
    }

    protected static function fromResponse(ResponseInterface $response, $message = null)
    {
        return new static($message, $response->getStatusCode(), (string)$response->getBody());
    }

    public static function oauthException(ResponseInterface $response, $data)
    {
        return static::fromResponse($response, $data['error'] ?? $response->getReasonPhrase());
    }
}
