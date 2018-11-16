<?php

namespace Vairogs\Utils\Oauth\Client\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

abstract class BaseProvider extends AbstractProvider
{
    public $generator;

    public function __construct(array $options = [], array $collaborators = [], UrlGeneratorInterface $generator = null)
    {
        $this->generator = $generator;
        parent::__construct($options, $collaborators);
    }

    public function getRedirectUri(): string
    {
        return $this->redirectUri;
    }

    /**
     * @param  mixed $grant
     * @param  array $options
     * @param array $attributes
     *
     * @return AccessToken
     * @throws RouteNotFoundException
     * @throws MissingMandatoryParametersException
     * @throws InvalidParameterException
     */
    public function getAccessToken($grant, array $options = [], array $attributes = []): AccessToken
    {
        $grant = $this->verifyGrant($grant);
        $redirectUri = null;
        if (!empty($attributes) && $this->generator) {
            $redirectUri = $this->generator->generate($attributes['_route'], $attributes['_route_params'], UrlGeneratorInterface::ABSOLUTE_URL);
        }
        $params = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $redirectUri ?: $this->redirectUri,
        ];
        $params = $grant->prepareRequestParameters($params, $options);
        $request = $this->getAccessTokenRequest($params);
        $response = $this->getParsedResponse($request);
        $prepared = $this->prepareAccessTokenResponse($response);

        return $this->createAccessToken($prepared, $grant);
    }

    public function setState($state = null): void
    {
        $this->state = $state;
    }
}
