<?php

namespace Vairogs\Utils\Oauth\Client\Client;

use Vairogs\Utils\Core\Exception\ErrorException;
use Vairogs\Utils\Oauth\Client\OAuth2Client;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

class GoogleOAuth2Client extends OAuth2Client
{
    public function __construct($provider, RequestStack $requestStack)
    {
        parent::__construct($provider, $requestStack);
        $this->isStateless = false;
    }

    /**
     * @param array $scopes
     * @param array $options
     * @param null $state
     *
     * @return RedirectResponse
     * @throws ErrorException
     */
    public function redirect(array $scopes = [], array $options = [], $state = null): RedirectResponse
    {
        if (!empty($scopes)) {
            $options['scope'] = $scopes;
        }
        if (!$this->isStateless) {
            $this->getSession()->set(self::OAUTH2_SESSION_STATE_KEY, $state ?: $this->provider->getState());
            if ($state) {
                $this->provider->setState($state);
            }
        }
        $options['state'] = $state;
        $url = $this->provider->getAuthorizationUrl($options);

        return new RedirectResponse($url);
    }
}
