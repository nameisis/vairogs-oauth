<?php

namespace Vairogs\Utils\Oauth\Client\Provider\Steam;

use Vairogs\Utils\Oauth\Component\HaveEmailInterface;
use Vairogs\Utils\Oauth\Component\InterfaceTrait;
use Vairogs\Utils\Oauth\Component\SocialUserInterface;

class SteamUser implements SocialUserInterface, HaveEmailInterface
{
    use InterfaceTrait;

    /**
     * @var bool
     */
    protected $returnsEmail = false;

    /**
     * @return bool
     */
    public function returnsEmail(): bool
    {
        return $this->returnsEmail;
    }
}
