<?php

namespace Vairogs\Utils\Oauth\Client\Provider\Google;

use Vairogs\Utils\Oauth\Component\HaveEmailInterface;
use Vairogs\Utils\Oauth\Component\InterfaceVariableTrait;
use Vairogs\Utils\Oauth\Component\SocialUserInterface;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class GoogleUser implements ResourceOwnerInterface, SocialUserInterface, HaveEmailInterface
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
     * @param array $response
     */
    public function __construct(array $response)
    {
        $this->response = $response;
    }

    /**
     * @return int
     */
    public function getId(): ?int
    {
        if ($this->id) {
            $this->id = (int)$this->response['id'];
        }

        return $this->id;
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
        if (!$this->email && !empty($this->response['emails'])) {
            $this->email = $this->response['emails'][0]['value'];
        }

        return $this->email;
    }

    /**
     * @return string
     */
    public function getFirstName(): ?string
    {
        if (!$this->firstName) {
            $this->firstName = $this->response['name']['givenName'];
        }

        return $this->firstName;
    }

    /**
     * @return string
     */
    public function getLastName(): ?string
    {
        if (!$this->lastName) {
            $this->lastName = $this->response['name']['familyName'];
        }

        return $this->lastName;
    }

    /**
     * @return string
     */
    public function getUsername(): ?string
    {
        if (!$this->username) {
            $username = \explode('@', $this->email);
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
     * @return string
     */
    public function getName(): ?string
    {
        return $this->response['displayName'];
    }

    /**
     * @return string|null
     */
    public function getAvatar(): ?string
    {
        if (!empty($this->response['image']['url'])) {
            return $this->response['image']['url'];
        }

        return '';
    }
}
