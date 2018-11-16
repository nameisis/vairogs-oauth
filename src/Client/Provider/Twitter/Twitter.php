<?php

namespace Vairogs\Utils\Oauth\Client\Provider\Twitter;

use Abraham\TwitterOAuth\TwitterOAuth;
use GuzzleHttp\Client as HttpClient;
use League\OAuth2\Client\Grant\GrantFactory;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\RequestFactory;
use Psr\Http\Message\ResponseInterface;

class Twitter extends AbstractProvider
{
    public const URL_REQUEST_TOKEN = 'oauth/request_token';

    public const URL_AUTHORIZE = 'oauth/authorize';

    public const URL_ACCESS_TOKEN = 'oauth/access_token';

    public $twitter;

    /**
     * @param array $options
     * @param array $collaborators
     */
    public function __construct(array $options = [], array $collaborators = [])
    {
        parent::__construct($options, $collaborators);
        foreach ($options as $option => $value) {
            if (\property_exists($this, $option)) {
                $this->{$option} = $value;
            }
        }
        if (empty($collaborators['grantFactory'])) {
            $collaborators['grantFactory'] = new GrantFactory();
        }
        $this->setGrantFactory($collaborators['grantFactory']);
        if (empty($collaborators['requestFactory'])) {
            $collaborators['requestFactory'] = new RequestFactory();
        }
        $this->setRequestFactory($collaborators['requestFactory']);
        if (empty($collaborators['httpClient'])) {
            $client_options = $this->getAllowedClientOptions($options);
            $collaborators['httpClient'] = new HttpClient(\array_intersect_key($options, \array_flip($client_options)));
        }
        $this->setHttpClient($collaborators['httpClient']);
        $this->twitter = new TwitterOAuth($this->getClientId(), $this->getClientSecret());
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    public function getRedirectUri(): string
    {
        return $this->redirectUri;
    }

    protected function checkResponse(ResponseInterface $response, $data): void
    {
    }

    protected function createResourceOwner(array $response, AccessToken $token = null)
    {
    }

    public function getBaseAccessTokenUrl(array $params = null)
    {
    }

    public function getBaseAuthorizationUrl()
    {
    }

    public function getDefaultScopes(): array
    {
        return [];
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
    }
}
