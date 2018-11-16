<?php

namespace Vairogs\Utils\Oauth\Client\Provider\Twitter;

use Vairogs\Utils\Oauth\Component\HaveEmailInterface;
use Vairogs\Utils\Oauth\Component\InterfaceVariableTrait;
use Vairogs\Utils\Oauth\Component\SocialUserInterface;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class TwitterUser implements ResourceOwnerInterface, SocialUserInterface, HaveEmailInterface
{
    use InterfaceVariableTrait;

    /**
     * @var array
     */
    protected $response;

    /**
     * @var bool
     */
    protected $returnsEmail = false;

    /**
     * @param  array $response
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
        if (!$this->id) {
            $this->id = (int)$this->response['user_id'];
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
        if (!$this->email && isset($this->response['email'])) {
            $this->email = $this->response['email'];
        }

        return $this->email;
    }

    /**
     * @return string
     */
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * @return string
     */
    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    /**
     * @return string
     */
    public function getUsername(): ?string
    {
        if (!$this->username) {
            $this->username = \preg_replace('/[^a-z\d]/i', '', $this->response['screen_name']);
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
}
