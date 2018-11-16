<?php

namespace Vairogs\Utils\Oauth\Provider;

use Vairogs\Utils\Core\Exception\ErrorException;
use Vairogs\Utils\Core\Specification;
use Vairogs\Utils\Core\Util\Generator;
use Vairogs\Utils\Oauth\Component\Providerable;
use Vairogs\Utils\Oauth\Exception\InvalidTokenException;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use League\OAuth2\Client\Grant\AbstractGrant;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use UnexpectedValueException;

abstract class OpenIDConnectProvider extends AbstractVariables implements Providerable
{
    public const METHOD_POST = 'POST';
    public const METHOD_GET = 'GET';

    /**
     * @param array $options
     * @param array $collaborators
     * @param Router $router
     * @param Session $session
     */
    public function __construct(array $options = [], array $collaborators = [], Router $router, Session $session)
    {
        $this->signer = new Sha256();
        $this->validatorChain = new Specification\ValidatorChain();
        $this->validatorChain->setValidators([
            new Specification\NotEmpty('iat', true),
            new Specification\GreaterOrEqualsTo('exp', true),
            new Specification\EqualsTo('iss', true),
            new Specification\EqualsTo('aud', true),
            new Specification\NotEmpty('sub', true),
            new Specification\LesserOrEqualsTo('nbf'),
            new Specification\EqualsTo('jti'),
            new Specification\EqualsTo('azp'),
            new Specification\EqualsTo('nonce'),
        ]);
        $this->router = $router;
        $this->session = $session;
        parent::__construct($options, $collaborators);
        $this->buildParams($options);
    }

    private function buildParams(array $options = [])
    {
        if (!empty($options)) {
            $this->clientId = $options['client_key'];
            $this->clientSecret = $options['client_secret'];
            unset($options['client_secret'], $options['client_key']);
            $this->idTokenIssuer = $options['id_token_issuer'];
            $this->publicKey = 'file://'.$options['public_key'];
            $this->state = $this->getRandomState();
            $this->baseUri = $options['base_uri'];
            $this->useSession = $options['use_session'];
            $url = null;
            switch ($options['redirect']['type']) {
                case 'uri':
                    $url = $options['redirect']['uri'];
                    break;
                case 'route':
                    $params = !empty($options['redirect']['params']) ? $options['redirect']['params'] : [];
                    $url = $this->router->generate($options['redirect']['route'], $params, UrlGeneratorInterface::ABSOLUTE_URL);
                    break;
            }
            $this->redirectUri = $url;
            $this->buildUris($options);
        }
    }

    /**
     * @inheritdoc
     */
    protected function getRandomState($length = 32): string
    {
        return Generator::getUniqueId($length);
    }

    private function buildUris(array $options = [])
    {
        foreach ($options['uris'] as $name => $uri) {
            $opt = [
                'client_id' => $this->clientId,
                'redirect_uri' => $this->redirectUri,
                'state' => $this->state,
                'base_uri' => $this->baseUri,
            ];
            $method = $uri['method'] ?? self::METHOD_POST;
            $this->uris[$name] = new Uri($uri, $opt, $this->useSession, $method, $this->session);
        }
    }

    /**
     * @param $token
     * @param array $options
     *
     * @return array|ResponseInterface
     * @throws IdentityProviderException
     */
    public function getRefreshToken($token, array $options = [])
    {
        $params = [
            'token' => $token,
            'grant_type' => 'refresh_token',
        ];
        $params = \array_merge($params, $options);
        $request = $this->getRefreshTokenRequest($params);

        return $this->getResponse($request);
    }

    protected function getRefreshTokenRequest(array $params)
    {
        $method = $this->getAccessTokenMethod();
        $url = $this->getRefreshTokenUrl();
        $options = $this->getAccessTokenOptions($params);

        return $this->getRequest($method, $url, $options);
    }

    /**
     * @param  array $params
     *
     * @return array
     */
    protected function getAccessTokenOptions(array $params): array
    {
        $options = $this->getBaseTokenOptions($params);
        $options['headers']['authorization'] = 'Basic: '.\base64_encode($this->clientId.':'.$this->clientSecret);

        return $options;
    }

    /**
     * @param  mixed $grant
     * @param  array $options
     *
     * @return AccessToken
     * @throws ErrorException
     * @throws IdentityProviderException
     * @throws InvalidTokenException
     */
    public function getAccessToken($grant, array $options = []): AccessToken
    {
        /** @var AccessToken $token */
        $accessToken = $this->getAccessTokenFunction($grant, $options);
        if (null === $accessToken) {
            throw new InvalidTokenException('Invalid access token.');
        }
        $token = $accessToken->getIdToken();
        if (null === $token) {
            throw new InvalidTokenException('Expected an id_token but did not receive one from the authorization server.');
        }
        if (false === $token->verify($this->signer, $this->getPublicKey())) {
            throw new InvalidTokenException('Received an invalid id_token from authorization server.');
        }
        $currentTime = \time();
        $data = [
            'iss' => $this->getIdTokenIssuer(),
            'exp' => $currentTime,
            'auth_time' => $currentTime,
            'iat' => $currentTime,
            'nbf' => $currentTime,
            'aud' => $this->clientId,
        ];
        if ($token->hasClaim('azp')) {
            $data['azp'] = $this->clientId;
        }
        if (false === $this->validatorChain->validate($data, $token)) {
            throw new InvalidTokenException('The id_token did not pass validation.');
        }
        $this->saveSession($accessToken);

        return $accessToken;
    }

    protected function getBaseTokenOptions(array $params)
    {
        $options = [
            'headers' => [
                'content-type' => 'application/x-www-form-urlencoded',
            ],
        ];
        if ($this->getAccessTokenMethod() === self::METHOD_POST) {
            $options['body'] = $this->getAccessTokenBody($params);
        }

        return $options;
    }

    /**
     * @param RequestInterface $request
     *
     * @return array|ResponseInterface
     * @throws IdentityProviderException
     */
    public function getResponse(RequestInterface $request)
    {
        $response = $this->sendRequest($request);
        $this->statusCode = $response->getStatusCode();
        $parsed = $this->parseResponse($response);
        $this->checkResponse($response, $parsed);

        return $parsed;
    }

    protected function getAllowedClientOptions(array $options): array
    {
        return [
            'timeout',
            'proxy',
            'verify',
        ];
    }

    /**
     * @inheritdoc
     *
     * @param $grant
     * @param array $options
     *
     * @return AccessToken
     * @throws ErrorException
     * @throws IdentityProviderException
     */
    public function getAccessTokenFunction($grant, array $options = []): AccessToken
    {
        $grant = $this->verifyGrant($grant);
        $params = [
            'redirect_uri' => $this->redirectUri,
        ];
        $params = $grant->prepareRequestParameters($params, $options);
        $request = $this->getAccessTokenRequest($params);
        $response = $this->getResponse($request);
        if (!\is_array($response)) {
            throw new ErrorException('Invalid request parameters');
        }
        $prepared = $this->prepareAccessTokenResponse($response);

        return $this->createAccessToken($prepared, $grant);
    }

    protected function parseJson($content)
    {
        if (empty($content)) {
            return [];
        }
        $content = \json_decode($content, true);
        if (\json_last_error() !== \JSON_ERROR_NONE) {
            throw new UnexpectedValueException(\sprintf('Failed to parse JSON response: %s', \json_last_error_msg()));
        }

        return $content;
    }

    /**
     * @param $token
     * @param array $options
     *
     * @return array|ResponseInterface
     * @throws IdentityProviderException
     */
    public function getValidateToken($token, array $options = [])
    {
        $params = [
            'token' => $token,
        ];
        $params = \array_merge($params, $options);
        $request = $this->getValidateTokenRequest($params);

        return $this->getResponse($request);
    }

    protected function getValidateTokenRequest(array $params)
    {
        $method = $this->getAccessTokenMethod();
        $url = $this->getValidateTokenUrl();
        $options = $this->getBaseTokenOptions($params);

        return $this->getRequest($method, $url, $options);
    }

    /**
     * @param $token
     * @param array $options
     *
     * @return array|ResponseInterface
     * @throws IdentityProviderException
     */
    public function getRevokeToken($token, array $options = [])
    {
        $params = [
            'token' => $token,
        ];
        $params = \array_merge($params, $options);
        $request = $this->getRevokeTokenRequest($params);

        return $this->getResponse($request);
    }

    protected function getRevokeTokenRequest(array $params)
    {
        $method = $this->getAccessTokenMethod();
        $url = $this->getRevokeTokenUrl();
        $options = $this->getAccessTokenOptions($params);

        return $this->getRequest($method, $url, $options);
    }

    /**
     * @param  array $response
     * @param AbstractGrant|null $grant
     *
     * @return AccessToken
     */
    protected function createAccessToken(array $response, AbstractGrant $grant = null): AccessToken
    {
        if ($this->check($response)) {
            return new AccessToken($response);
        }

        return null;
    }

    public function getPublicKey()
    {
        return new Key($this->publicKey);
    }

    private function saveSession($accessToken)
    {
        if ($this->useSession) {
            $this->session->set('access_token', $accessToken->getToken());
            $this->session->set('refresh_token', $accessToken->getRefreshToken());
            $this->session->set('id_token', $accessToken->getIdTokenHint());
        }
    }
}
