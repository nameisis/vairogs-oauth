<?php

namespace Vairogs\Utils\Oauth\Client\Provider\Facebook;

use Vairogs\Utils\Oauth\Client\Provider\BaseProvider;
use Vairogs\Utils\Oauth\Exception\FacebookProviderException;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Facebook extends BaseProvider
{
    public const FIELDS = 'id;name;first_name;last_name;email;hometown;picture.type(large){url,is_silhouette};cover{source};locale;timezone';

    public const BASE_FACEBOOK_URL = 'https://www.facebook.com/';

    public const BASE_GRAPH_URL = 'https://graph.facebook.com/';

    public const GRAPH_API_VERSION_REGEX = '~^v\d+\.\d+$~';

    protected $fields = [];

    /**
     * @var string
     */
    protected $graphApiVersion;

    /**
     * @param array $options
     * @param array $collaborators
     * @param UrlGeneratorInterface|null $generator
     *
     * @throws InvalidArgumentException
     */
    public function __construct($options = [], array $collaborators = [], UrlGeneratorInterface $generator = null)
    {
        parent::__construct($options, $collaborators, $generator);
        if (empty($options['graphApiVersion'])) {
            throw new InvalidArgumentException('error_facebook_graph_api_version_not_set');
        }
        if (!\preg_match(self::GRAPH_API_VERSION_REGEX, $options['graphApiVersion'])) {
            throw new InvalidArgumentException('error_facebook_wrong_graph_api_version');
        }
        $this->graphApiVersion = $options['graphApiVersion'];
        if (!empty($options['fields'])) {
            $this->fields = $options['fields'];
        } else {
            $this->fields = \explode(';', self::FIELDS);
        }
    }

    /**
     * @param $accessToken
     *
     * @return AccessToken
     * @throws FacebookProviderException
     */
    public function getLongLivedAccessToken($accessToken): AccessToken
    {
        $params = [
            'fb_exchange_token' => (string)$accessToken,
        ];

        return $this->getAccessToken('fb_exchange_token', $params);
    }

    /**
     * @param string $grant
     * @param array $params
     * @param array $attributes
     *
     * @return AccessToken
     * @throws FacebookProviderException
     */
    public function getAccessToken($grant = 'authorization_code', array $params = [], array $attributes = []): AccessToken
    {
        if (isset($params['refresh_token'])) {
            throw new FacebookProviderException('error_facebook_token_refresh_not_supported');
        }

        return parent::getAccessToken($grant, $params, $attributes);
    }

    public function getBaseAccessTokenUrl(array $params): string
    {
        return $this->getBaseGraphUrl().$this->graphApiVersion.'/oauth/access_token';
    }

    /**
     * @return string
     */
    private function getBaseGraphUrl(): string
    {
        return static::BASE_GRAPH_URL;
    }

    public function getBaseAuthorizationUrl(): string
    {
        return $this->getBaseFacebookUrl().$this->graphApiVersion.'/dialog/oauth';
    }

    /**
     * @return string
     */
    private function getBaseFacebookUrl(): string
    {
        return static::BASE_FACEBOOK_URL;
    }

    /**
     * @inheritdoc
     */
    protected function getContentType(ResponseInterface $response): string
    {
        $type = parent::getContentType($response);
        if (\strpos($type, 'javascript') !== false) {
            return 'application/json';
        }
        if (\strpos($type, 'plain') !== false) {
            return 'application/x-www-form-urlencoded';
        }

        return $type;
    }

    public function getDefaultScopes(): array
    {
        return [
            'public_profile',
            'email',
        ];
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token): string
    {
        $appSecretProof = AppSecretProof::create($this->clientSecret, $token->getToken());

        return $this->getBaseGraphUrl().$this->graphApiVersion.'/me?fields='.\implode(',', $this->fields).'&access_token='.$token.'&appsecret_proof='.$appSecretProof;
    }

    protected function checkResponse(ResponseInterface $response, $data): void
    {
        if (!empty($data['error'])) {
            throw new IdentityProviderException('error_facebook_bad_response', 400, $response->getBody());
        }
    }

    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new FacebookUser($response);
    }
}
