<?php

namespace Vairogs\Utils\Oauth\DependencyInjection;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ProviderFactory
{
    private $generator;
    private $request;

    public function __construct(UrlGeneratorInterface $generator, RequestStack $requestStack)
    {
        $this->generator = $generator;
        $this->request = $requestStack->getCurrentRequest();
    }

    public function createProvider($class, array $options, array $redirectParams = [])
    {
        $options['redirectUri'] = $this->generateUrl($options['redirect_route'], $redirectParams);
        $collaborators = [];

        return new $class($options, $collaborators, $this->generator);
    }

    public function generateUrl($redirectUri = null, array $redirectParams = []): ?string
    {
        $this->getUrlToken($redirectParams);
        $redirectUri = $this->generator->generate($redirectUri, $redirectParams, UrlGeneratorInterface::ABSOLUTE_URL);

        return $redirectUri;
    }

    private function getUrlToken(array &$redirectParams = []): void
    {
        if (\array_key_exists('token', $redirectParams) && $redirectParams['token'] === null) {
            $redirectParams['token'] = \base64_encode(\http_build_query($this->request->query->all()));
        }
    }
}
