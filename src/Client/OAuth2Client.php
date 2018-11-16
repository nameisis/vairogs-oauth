<?php

namespace Vairogs\Utils\Oauth\Client;

use Vairogs\Utils\Core\Exception\ErrorException;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class OAuth2Client
{
    public const OAUTH2_SESSION_STATE_KEY = 'vairogs.utils.oauth.client_state';

    protected $provider;
    protected $requestStack;
    protected $isStateless = true;

    public function __construct($provider, RequestStack $requestStack)
    {
        $this->provider = $provider;
        $this->requestStack = $requestStack;
    }

    public function setAsStateless(): void
    {
        $this->isStateless = false;
    }

    /**
     * @param array $scopes
     * @param array $options
     * @param null $token
     *
     * @return RedirectResponse
     * @throws ErrorException
     */
    public function redirect(array $scopes = [], array $options = [], $token = null): RedirectResponse
    {
        if (!empty($scopes)) {
            $options['scope'] = $scopes;
        }
        if ($token) {
            $options['token'] = $token;
        }
        $url = $this->provider->getAuthorizationUrl($options);
        if (!$this->isStateless) {
            $this->getSession()->set(self::OAUTH2_SESSION_STATE_KEY, $this->provider->getState());
        }

        return new RedirectResponse($url);
    }

    /**
     * @return null|SessionInterface
     * @throws ErrorException
     */
    protected function getSession(): ?SessionInterface
    {
        $session = $this->getCurrentRequest()->getSession();
        if (!$session) {
            throw new ErrorException('In order to use "state", you must have a session. Set the OAuth2Client to stateless to avoid state');
        }

        return $session;
    }

    /**
     * @return null|Request
     * @throws ErrorException
     */
    protected function getCurrentRequest(): ?Request
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            throw new ErrorException('There is no "current request", and it is needed to perform this action');
        }

        return $request;
    }

    /**
     * @param array $attributes
     *
     * @return mixed
     * @throws ErrorException
     */
    public function fetchUser(array $attributes = [])
    {
        $token = $this->getAccessToken($attributes);

        return $this->fetchUserFromToken($token);
    }

    /**
     * @param array $attributes
     *
     * @return mixed
     * @throws ErrorException
     */
    public function getAccessToken(array $attributes = [])
    {
        if (!$this->isStateless) {
            $expectedState = $this->getSession()->get(self::OAUTH2_SESSION_STATE_KEY);
            $actualState = $this->getCurrentRequest()->query->get('state');
            if (!$actualState || ($actualState !== $expectedState)) {
                throw new ErrorException('Invalid state');
            }
        }
        $code = $this->getCurrentRequest()->get('code');
        if (!$code) {
            throw new ErrorException('No "code" parameter was found');
        }

        return $this->provider->getAccessToken('authorization_code', [
            'code' => $code,
        ], $attributes);
    }

    public function fetchUserFromToken(AccessToken $accessToken)
    {
        return $this->provider->getResourceOwner($accessToken);
    }

    public function getOAuth2Provider()
    {
        return $this->provider;
    }
}
