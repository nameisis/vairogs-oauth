<?php

namespace Vairogs\Utils\Oauth\Client\Provider\Draugiem;

use Vairogs\Utils\Oauth\Component\HaveEmailInterface;
use Vairogs\Utils\Oauth\Component\InterfaceTrait;
use Vairogs\Utils\Oauth\Component\SocialUserInterface;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class DraugiemUser implements ResourceOwnerInterface, SocialUserInterface, HaveEmailInterface
{
    use InterfaceTrait;

    /**
     * @var array
     */
    protected $response;

    protected $userData;

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
        $this->userData = \reset($this->response['users']);
        $this->id = (int)$this->response['uid'];
        $this->firstName = $this->getField('name');
        $this->lastName = $this->getField('surname');
        $this->username = \preg_replace('/[^a-z\d]/i', '', $this->getField('url'));
    }

    /**
     * @param string $key
     *
     * @return mixed|null
     */
    private function getField($key)
    {
        return $this->userData[$key] ?? null;
    }

    /**
     * @return bool
     */
    public function returnsEmail(): bool
    {
        return $this->returnsEmail;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->response;
    }
}
