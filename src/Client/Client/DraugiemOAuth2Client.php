<?php

namespace Vairogs\Utils\Oauth\Client\Client;

use Vairogs\Utils\Core\Exception\VairogsException;
use Vairogs\Utils\Oauth\Client\OAuth2Client;
use Vairogs\Utils\Oauth\Client\Provider\Draugiem\DraugiemUser;
use Symfony\Component\HttpFoundation\RedirectResponse;

class DraugiemOAuth2Client extends OAuth2Client
{
    public const METHOD = 'POST';

    public function fetchUser(array $attributes = [])
    {
        $user = $this->returnRedirect();

        return new DraugiemUser($user);
    }

    /**
     * @return mixed
     * @throws VairogsException
     */
    public function returnRedirect()
    {
        $data = [
            'app' => $this->provider->getClientSecret(),
            'code' => $this->getCurrentRequest()->get('dr_auth_code'),
            'action' => 'authorize',
        ];
        $url = $this->provider->getBaseAccessTokenUrl().'?'.\http_build_query($data);
        $factory = $this->provider->getRequestFactory();
        $request = $factory->getRequestWithOptions(static::METHOD, $url, $data);

        return $this->provider->getParsedResponse($request);
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
        $data = [
            'hash' => \md5($this->provider->getClientSecret().$this->provider->getRedirectUri()),
            'redirect' => $this->provider->getRedirectUri(),
            'app' => $this->provider->getClientId(),
        ];
        $url = $this->provider->getBaseAuthorizationUrl().'?'.\http_build_query($data);
        if (!$this->isStateless) {
            $this->getSession()->set(self::OAUTH2_SESSION_STATE_KEY, $this->provider->getState());
        }

        return new RedirectResponse($url);
    }
}
