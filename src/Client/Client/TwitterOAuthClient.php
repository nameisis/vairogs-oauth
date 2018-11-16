<?php

namespace Vairogs\Utils\Oauth\Client\Client;

use Abraham\TwitterOAuth\TwitterOAuth;
use Exception;
use Vairogs\Utils\Core\Exception\VairogsException;
use Vairogs\Utils\Oauth\Client\OAuth2Client;
use Vairogs\Utils\Oauth\Client\Provider\Twitter\TwitterUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

class TwitterOAuthClient extends OAuth2Client
{
    public const URL_REQUEST_TOKEN = 'oauth/request_token';
    public const URL_AUTHORIZE = 'oauth/authorize';
    public const URL_ACCESS_TOKEN = 'oauth/access_token';
    protected $session;

    public function __construct($provider, RequestStack $requestStack)
    {
        parent::__construct($provider, $requestStack);
        $this->session = $this->requestStack->getCurrentRequest()->getSession();
    }

    /**
     * @param array $attributes
     *
     * @return TwitterUser|mixed
     * @throws VairogsException
     */
    public function fetchUser(array $attributes = [])
    {
        $code = $this->getCurrentRequest()->get('oauth_verifier');
        $this->provider->twitter = new TwitterOAuth($this->provider->getClientId(), $this->provider->getClientSecret(), $this->session->get('oauth_token'), $this->session->get('oauth_token_secret'));
        try {
            $user_token = $this->provider->twitter->oauth(static::URL_ACCESS_TOKEN, ['oauth_verifier' => $code]);
        } catch (Exception $e) {
            if ($this->provider->twitter->getLastHttpCode() !== RedirectResponse::HTTP_OK) {
                throw new VairogsException($e->getMessage());
            }
        }

        return new TwitterUser($user_token);
    }

    /**
     * @param array $attributes
     *
     * @return mixed
     * @throws VairogsException
     */
    public function getAccessToken(array $attributes = [])
    {
        if (!$this->isStateless) {
            $expectedState = $this->getSession()->get(self::OAUTH2_SESSION_STATE_KEY);
            $actualState = $this->getCurrentRequest()->query->get('state');
            if (!$actualState || ($actualState !== $expectedState)) {
                throw new VairogsException('Invalid state: '.\serialize($actualState).', '.\serialize($expectedState));
            }
        }
        $code = $this->getCurrentRequest()->get('oauth_verifier');
        $token = $this->getCurrentRequest()->get('oauth_token');
        if (!$code) {
            throw new VairogsException('No "oauth_verifier" parameter was found');
        }

        return $this->provider->getAccessToken('authorization_code', [
            'verifier' => $code,
            'token' => $token,
            'code' => $code,
        ]);
    }

    /**
     * @param array $scopes
     * @param array $options
     * @param null $token
     *
     * @return RedirectResponse
     * @throws VairogsException
     */
    public function redirect(array $scopes = [], array $options = [], $token = null): RedirectResponse
    {
        $url = $this->provider->twitter->url(static::URL_AUTHORIZE, ['oauth_token' => $this->getRequestToken()]);
        if (!$this->isStateless) {
            $this->getSession()->set(self::OAUTH2_SESSION_STATE_KEY, $this->provider->getState());
        }

        return new RedirectResponse($url);
    }

    /**
     * @return mixed
     * @throws VairogsException
     */
    public function getRequestToken()
    {
        $request_token = $this->provider->twitter->oauth(static::URL_REQUEST_TOKEN, ['oauth_callback' => $this->provider->getRedirectUri()]);
        if ($this->provider->twitter->getLastHttpCode() !== RedirectResponse::HTTP_OK) {
            throw new VairogsException('There was a problem performing this request');
        }
        $this->session->set('oauth_token', $request_token['oauth_token']);
        $this->session->set('oauth_token_secret', $request_token['oauth_token_secret']);

        return $request_token['oauth_token'];
    }
}
