<?php

namespace Vairogs\Utils\Oauth\Client\Provider\Facebook;

use Vairogs\Utils\Oauth\Component\HaveEmailInterface;
use Vairogs\Utils\Oauth\Component\InterfaceVariableTrait;
use Vairogs\Utils\Oauth\Component\SocialUserInterface;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use stdClass;

class FacebookUser implements ResourceOwnerInterface, SocialUserInterface, HaveEmailInterface
{
    use InterfaceVariableTrait;

    /**
     * @var array
     */
    protected $response;

    /**
     * @var bool
     */
    protected $returnsEmail = true;

    /**
     * @param  array $response
     */
    public function __construct(array $response)
    {
        $this->response = $response;
        if (!empty($response['picture']['data']['url'])) {
            $this->response['picture_url'] = $response['picture']['data']['url'];
        }
        if (isset($response['picture']['data']['is_silhouette'])) {
            $this->response['is_silhouette'] = $response['picture']['data']['is_silhouette'];
        }
        if (!empty($response['cover']['source'])) {
            $this->response['cover_photo_url'] = $response['cover']['source'];
        }
    }

    /**
     * @return int
     */
    public function getId(): ?int
    {
        if (!$this->id) {
            $this->id = (int)$this->getField('id');
        }

        return $this->id;
    }

    /**
     * @param string $key
     *
     * @return mixed|null
     */
    private function getField($key)
    {
        return $this->response[$key] ?? null;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->response;
    }

    /**
     * @return string
     */
    public function getEmail(): ?string
    {
        if (!$this->email) {
            $this->email = $this->getField('email');
        }

        return $this->email;
    }

    /**
     * @return string
     */
    public function getFirstName(): ?string
    {
        if (!$this->firstName) {
            $this->firstName = $this->getField('first_name');
        }

        return $this->firstName;
    }

    /**
     * @return string
     */
    public function getLastName(): ?string
    {
        if (!$this->lastName) {
            $this->lastName = $this->getField('last_name');
        }

        return $this->lastName;
    }

    /**
     * @return string
     */
    public function getUsername(): ?string
    {
        if (!$this->username) {
            $username = \explode('@', $this->getField('email'));
            $username = \preg_replace('/[^a-z\d]/i', '', $username[0]);
            $this->username = $username;
        }

        return $this->username;
    }

    /**
     * @return bool
     */
    public function returnsEmail(): bool
    {
        return $this->returnsEmail;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->getField('name');
    }

    /**
     * @return array|null
     */
    public function getHometown(): ?array
    {
        return $this->getField('hometown');
    }

    /**
     * @return boolean
     */
    public function isDefaultPicture(): ?bool
    {
        return $this->getField('is_silhouette');
    }

    /**
     * @return string|null
     */
    public function getPictureUrl(): ?string
    {
        return $this->getField('picture_url');
    }

    /**
     * @return string|null
     */
    public function getCoverPhotoUrl(): ?string
    {
        return $this->getField('cover_photo_url');
    }

    /**
     * @return string|null
     */
    public function getGender(): ?string
    {
        return $this->getField('gender');
    }

    /**
     * @return string|null
     */
    public function getLocale(): ?string
    {
        return $this->getField('locale');
    }

    /**
     * @return string|null
     */
    public function getLink(): ?string
    {
        return $this->getField('link');
    }

    /**
     * @return float|null
     */
    public function getTimezone(): ?float
    {
        return $this->getField('timezone');
    }

    public function getAgeRange(): ?stdClass
    {
        return $this->getField('age_range');
    }
}
