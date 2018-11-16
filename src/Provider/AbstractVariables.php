<?php

namespace Vairogs\Utils\Oauth\Provider;

use Vairogs\Utils\Core\Specification;
use Vairogs\Utils\Core\Specification\ValidatorChain;
use Lcobucci\JWT\Signer;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken as BaseAccessToken;
use Psr\Http\Message\ResponseInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Session\Session;

abstract class AbstractVariables extends AbstractProvider
{
    /**
     * @var string
     */
    protected $publicKey;

    /**
     * @var Signer
     */
    protected $signer;

    /**
     * @var Specification\ValidatorChain
     */
    protected $validatorChain;

    /**
     * @var string
     */
    protected $idTokenIssuer;

    /**
     * @var Uri[]
     */
    protected $uris = [];

    /**
     * @var bool
     */
    protected $useSession;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var int
     */
    protected $statusCode;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var string
     */
    protected $baseUri;

    public function check($response = null): bool
    {
        return $response !== null;
    }

    /**
     * @return Specification\ValidatorChain
     */
    public function getValidatorChain(): ValidatorChain
    {
        return $this->validatorChain;
    }

    public function getUri($name)
    {
        return $this->uris[$name];
    }

    /**
     * @return mixed
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @param mixed $statusCode
     *
     * @return $this
     */
    public function setStatusCode($statusCode): self
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    abstract public function getValidateTokenUrl();

    abstract public function getRefreshTokenUrl();

    abstract public function getRevokeTokenUrl();

    protected function checkResponse(ResponseInterface $response, $data): void
    {
    }

    protected function createResourceOwner(array $response, BaseAccessToken $token)
    {
        return [];
    }

    public function getBaseAccessTokenUrl(array $params): string
    {
        return '';
    }

    public function getBaseAuthorizationUrl(): string
    {
        return '';
    }

    public function getDefaultScopes(): array
    {
        return [];
    }

    public function getResourceOwnerDetailsUrl(BaseAccessToken $token)
    {
    }

    /**
     * @return string
     */
    protected function getScopeSeparator(): string
    {
        return ' ';
    }

    /**
     * @return string
     */
    protected function getIdTokenIssuer(): string
    {
        return $this->idTokenIssuer;
    }

    /**
     * @return array
     */
    protected function getRequiredOptions(): array
    {
        return [];
    }
}
