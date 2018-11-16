<?php

namespace Vairogs\Utils\Oauth\Provider;

use Vairogs\Utils\Core\Exception\ErrorException;
use Vairogs\Utils\Oauth\Component\Uriable;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;

class Uri implements Uriable
{
    protected $params = [];
    protected $urlParams = [];
    protected $url;
    protected $base;
    protected $session;
    protected $useSession;
    protected $method;

    public function __construct(array $options, array $additional = [], $useSession = false, $method = OpenIDConnectProvider::METHOD_POST, Session $session = null)
    {
        $this->base = \rtrim($additional['base_uri'], '/').'/';
        unset($additional['base_uri']);
        $this->session = $session;
        $this->useSession = $useSession;
        $this->method = $method;
        $this->params = !empty($options['params']) ? $options['params'] : [];
        $this->setGetParams($options, $additional);
    }

    private function setGetParams($options, $additional): void
    {
        if ($this->method === OpenIDConnectProvider::METHOD_GET) {
            if (isset($options['url_params']['post_logout_redirect_uri'])) {
                $options['url_params']['post_logout_redirect_uri'] = $additional['redirect_uri'];
                unset($additional['redirect_uri']);
            }
            $this->urlParams = !empty($options['url_params']) ? \array_merge($options['url_params'], $additional) : $additional;
        }
    }

    public function addParam($value): void
    {
        $this->params[] = $value;
    }

    public function addUrlParam($name, $value): void
    {
        $this->urlParams[$name] = $value;
    }

    /**
     * @return string
     */
    public function getBase(): string
    {
        return $this->base;
    }

    /**
     * @return RedirectResponse
     * @throws ErrorException
     */
    public function redirect(): RedirectResponse
    {
        return new RedirectResponse($this->getUrl());
    }

    /**
     * @param null|string $language
     *
     * @return mixed
     * @throws ErrorException
     */
    public function getUrl($language = null)
    {
        $this->buildUrl($language);

        return $this->url;
    }

    /**
     * @param mixed $url
     *
     * @return $this
     */
    public function setUrl($url): self
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @param null|string $language
     *
     * @throws ErrorException
     */
    private function buildUrl($language = null): void
    {
        $this->setIdToken();
        if ($language !== null) {
            $this->urlParams['lang'] = (string)$language;
        }
        $url = $this->base;
        if (!empty($this->params)) {
            $url .= \implode('/', $this->params);
        }
        if (!empty($this->urlParams)) {
            $params = \http_build_query($this->urlParams);
            $url .= '?'.$params;
        }
        $url = \urldecode($url);
        $this->setUrl($url);
    }

    /**
     * @throws ErrorException
     */
    private function setIdToken(): void
    {
        if ($this->session !== null && $this->method === OpenIDConnectProvider::METHOD_GET && isset($this->urlParams['id_token_hint']) && $this->session->has('id_token')) {
            if ($this->useSession === false) {
                throw new ErrorException(\sprintf('"%s" parameter must be set to "true" in order to use id_token_hint', 'use_session'));
            }
            $this->urlParams['id_token_hint'] = $this->session->get('id_token');
        }
    }

}
