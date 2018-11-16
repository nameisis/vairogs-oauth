<?php

namespace Vairogs\Utils\Oauth\Client\Provider\Draugiem;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ResponseInterface;

class Draugiem extends AbstractProvider
{
    /**
     * Draugiem.lv API URL
     */
    public const API_URL = 'http://api.draugiem.lv/json/';

    /**
     * Draugiem.lv passport login URL
     */
    public const LOGIN_URL = 'https://api.draugiem.lv/authorize/';

    /**
     * Timeout in seconds for session_check requests
     */
    public const SESSION_CHECK_TIMEOUT = 180;

    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function getRedirectUri(): string
    {
        return $this->redirectUri;
    }

    protected function checkResponse(ResponseInterface $response, $data): void
    {
        if (!empty($data['error'])) {
            throw new IdentityProviderException('error_draugiem_bad_response', $data['error']['code'], $response->getBody());
        }
    }

    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new DraugiemUser($response);
    }

    public function getBaseAccessTokenUrl(array $params = []): string
    {
        return static::API_URL;
    }

    public function getBaseAuthorizationUrl(): string
    {
        return static::LOGIN_URL;
    }

    public function getDefaultScopes(): array
    {
        return [];
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
    }
}
