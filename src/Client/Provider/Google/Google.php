<?php

namespace Vairogs\Utils\Oauth\Client\Provider\Google;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

class Google extends AbstractProvider
{
    use BearerAuthorizationTrait;

    public const ACCESS_TOKEN_RESOURCE_OWNER_ID = 'id';
    /**
     * @var array
     * @link https://developers.google.com/+/web/api/rest/latest/people
     */
    protected static $defaultUserFields = [
        'id',
        'name(familyName,givenName)',
        'displayName',
        'emails/value',
        'image/url',
    ];
    /**
     * @var string
     * @link https://developers.google.com/accounts/docs/OAuth2WebServer#offline
     */
    protected $accessType;
    /**
     * @var string
     * @link https://developers.google.com/accounts/docs/OAuth2Login#hd-param
     */
    protected $hostedDomain;
    /**
     * @var array
     */
    protected $userFields = [];

    public function setState($state = null): void
    {
        $this->state = $state;
    }

    protected function checkResponse(ResponseInterface $response, $data): void
    {
        if (!empty($data['error'])) {
            $code = 0;
            $error = $data['error'];
            if (\is_array($error)) {
                $code = $error['code'];
            }
            throw new IdentityProviderException('error_google_bad_response', $code, $response->getBody());
        }
    }

    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new GoogleUser($response);
    }

    protected function getAuthorizationParameters(array $options): array
    {
        $input = [
            'hd' => $this->hostedDomain,
            'access_type' => $this->accessType,
            'authuser' => '-1',
        ];
        $params = \array_merge(parent::getAuthorizationParameters($options), \array_filter($input));

        return $params;
    }

    public function getBaseAccessTokenUrl(array $params): string
    {
        return 'https://accounts.google.com/o/oauth2/token';
    }

    public function getBaseAuthorizationUrl(): string
    {
        return 'https://accounts.google.com/o/oauth2/auth';
    }

    protected function getDefaultScopes(): array
    {
        return [
            'email',
            'openid',
            'profile',
        ];
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token): string
    {
        $fields = \array_merge(self::$defaultUserFields, $this->userFields);
        $input = [
            'fields' => \implode(',', $fields),
            'alt' => 'json',
        ];

        return 'https://www.googleapis.com/plus/v1/people/me?'.\http_build_query($input);
    }

    protected function getScopeSeparator(): string
    {
        return ' ';
    }
}
