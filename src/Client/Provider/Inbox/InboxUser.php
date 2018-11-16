<?php

namespace Vairogs\Utils\Oauth\Client\Provider\Inbox;

use Vairogs\Utils\Oauth\Component\HaveEmailInterface;
use Vairogs\Utils\Oauth\Component\InterfaceVariableTrait;
use Vairogs\Utils\Oauth\Component\SocialUserInterface;

class InboxUser implements SocialUserInterface, HaveEmailInterface
{
    use InterfaceVariableTrait;

    /**
     * @var array
     */
    protected $response;

    /**
     * @var int
     */
    protected $originalId;

    /**
     * @var bool
     */
    protected $returnsEmail = true;

    /**
     * @param  array $response
     * @param integer|null $id
     */
    public function __construct(array $response, $id = null)
    {
        $this->response = $response;
        $this->originalId = $id;
    }

    /**
     * @return int
     */
    public function getId(): ?int
    {
        if (!$this->id) {
            $this->id = $this->getField('openid_sreg_email');
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
     * @return string
     */
    public function getEmail(): ?string
    {
        if (!$this->email) {
            $this->email = $this->getField('openid_sreg_email');
        }

        return $this->email;
    }

    /**
     * @return string
     */
    public function getFirstName(): ?string
    {
        if (!$this->firstName) {
            $name = $this->getField('openid_sreg_fullname');
            $data = \explode(' ', $name, 2);
            $this->firstName = $data[0];
        }

        return $this->firstName;
    }

    /**
     * @return string
     */
    public function getLastName(): ?string
    {
        if (!$this->lastName) {
            $name = $this->getField('openid_sreg_fullname');
            $data = \explode(' ', $name, 2);
            $this->lastName = $data[1];
        }

        return $this->lastName;
    }

    /**
     * @return string
     */
    public function getUsername(): ?string
    {
        if (!$this->username) {
            $username = \explode('@', $this->originalId ?: $this->email);
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
     * @return int
     */
    public function getOriginalId(): int
    {
        return $this->originalId;
    }
}
