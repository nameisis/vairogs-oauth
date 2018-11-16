<?php

namespace Vairogs\Utils\Oauth\Login;

use Vairogs\Utils\Core\Exception\ErrorException;
use Vairogs\Utils\Core\Util\Helper;
use Vairogs\Utils\Oauth\Component\Loginable;
use Vairogs\Utils\Oauth\DependencyInjection\ProviderFactory;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Login implements Loginable
{
    use ContainerAwareTrait;

    /**
     * @var RequestStack
     */
    public $requestStack;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var UrlGeneratorInterface
     */
    protected $generator;
    protected $redirectRoute;
    protected $redirectRouteParams = [];
    protected $clientName;
    protected $apiKey;
    protected $openidUrl;
    protected $pregCheck;
    protected $profileUrl = false;
    protected $nsMode = 'auth';
    protected $sregFields = 'email';
    protected $userClass;
    protected $fields = [];

    public function __construct($clientName, RequestStack $requestStack, UrlGeneratorInterface $generator)
    {
        $this->request = $requestStack->getCurrentRequest();
        $this->requestStack = $requestStack;
        $this->generator = $generator;
        $this->clientName = $clientName;
    }

    public function makeParameters(): void
    {
        $this->setInputs($this->clientName);
        $this->nsMode = $this->container->getParameter($this->clientName.'.option.ns_mode') ?: $this->nsMode;
        $this->setParameters($this->clientName);
        if (!empty($this->fields) && \is_array($this->fields)) {
            $this->sregFields = \implode(',', $this->fields);
        }
    }

    private function setInputs($clientName): void
    {
        $inputs = [
            'apiKey' => $clientName.'.api_key',
            'openidUrl' => $clientName.'.openid_url',
            'pregCheck' => $clientName.'.preg_check',
            'userClass' => $clientName.'.user_class',
        ];
        foreach ($inputs as $key => $input) {
            $this->{$key} = $this->container->getParameter($input);
        }
    }

    private function setParameters($clientName): void
    {
        $parameters = [
            'profileUrl' => $clientName.'.option.profile_url',
            'redirectRoute' => $clientName.'.redirect_route',
            'redirectRouteParams' => $clientName.'.option.params',
            'fields' => $clientName.'.option.sreg_fields',
        ];
        foreach ($parameters as $key => $param) {
            if ($this->container->hasParameter($param)) {
                $this->{$key} = $this->container->getParameter($param);
            }
        }
    }

    /**
     * @throws ErrorException
     */
    public function fetchUser()
    {
        $user = $this->validate();
        if ($user !== null) {
            if ($this->profileUrl === false) {
                $user = new $this->userClass($this->request->query->all(), $user);
            } else {
                $user = $this->getData($user);
            }
        }
        if ($user === null) {
            throw new ErrorException('error_oauth_login_invalid_or_timed_out');
        }

        return $user;
    }

    /**
     * @param int $timeout
     *
     * @return string|null
     */
    public function validate($timeout = 30): ?string
    {
        $get = $this->request->query->all();
        $params = [
            'openid.assoc_handle' => $get['openid_assoc_handle'],
            'openid.signed' => $get['openid_signed'],
            'openid.sig' => $get['openid_sig'],
            'openid.ns' => 'http://specs.openid.net/auth/2.0',
        ];
        foreach (\explode(',', $get['openid_signed']) as $item) {
            $val = $get['openid_'.\str_replace('.', '_', $item)];
            $params['openid.'.$item] = \get_magic_quotes_gpc() ? \stripslashes($val) : $val;
        }
        $params['openid.mode'] = 'check_authentication';
        $data = \http_build_query($params);
        $context = \stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Accept-language: en\r\n"."Content-type: application/x-www-form-urlencoded\r\n".'Content-Length: '.\strlen($data)."\r\n",
                'content' => $data,
                'timeout' => $timeout,
            ],
        ]);
        \preg_match($this->pregCheck, \urldecode($get['openid_claimed_id']), $matches);
        $openID = (\is_array($matches) && isset($matches[1])) ? $matches[1] : null;

        return \preg_match("#is_valid\s*:\s*true#i", \file_get_contents($this->openidUrl.'/'.$this->apiKey, false, $context)) === 1 ? $openID : null;
    }

    private function getData($openID = null)
    {
        if ($openID) {
            $data = \file_get_contents($this->profileUrl.$openID);
            $json = \json_decode($data, true);

            return new $this->userClass($json['response'], $openID);
        }

        return null;
    }

    /**
     * @return RedirectResponse
     * @throws ErrorException
     */
    public function redirect(): RedirectResponse
    {
        $providerFactory = new ProviderFactory($this->generator, $this->requestStack);
        $redirectUri = $providerFactory->generateUrl($this->redirectRoute, $this->redirectRouteParams);

        return new RedirectResponse($this->urlPath($redirectUri));
    }

    /**
     * @param string $return
     * @param string|null $altRealm
     *
     * @return string
     * @throws ErrorException
     */
    public function urlPath($return = null, $altRealm = null): string
    {
        $realm = $altRealm ?: Helper::getSchema($this->request).$this->request->server->get('HTTP_HOST');
        if (null !== $return) {
            if (!$this->validateUrl($return)) {
                throw new ErrorException('error_oauth_invalid_return_url');
            }
        } else {
            $return = $realm.$this->request->server->get('SCRIPT_NAME');
        }

        return $this->openidUrl.'/'.$this->apiKey.'/?'.\http_build_query($this->getParams($return, $realm));
    }

    /**
     * @param string $url
     *
     * @return bool
     */
    private function validateUrl($url): bool
    {
        if (!\filter_var($url, \FILTER_VALIDATE_URL)) {
            return false;
        }

        return true;
    }

    private function getParams($return, $realm): array
    {
        $params = [
            'openid.ns' => 'http://specs.openid.net/auth/2.0',
            'openid.mode' => 'checkid_setup',
            'openid.return_to' => $return,
            'openid.realm' => $realm,
            'openid.identity' => 'http://specs.openid.net/auth/2.0/identifier_select',
            'openid.claimed_id' => 'http://specs.openid.net/auth/2.0/identifier_select',
        ];
        if ($this->nsMode === 'sreg') {
            $params['openid.ns.sreg'] = 'http://openid.net/extensions/sreg/1.1';
            $params['openid.sreg.required'] = $this->sregFields;
        }

        return $params;
    }
}
