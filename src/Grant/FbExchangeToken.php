<?php

namespace Vairogs\Utils\Oauth\Grant;

use League\OAuth2\Client\Grant\AbstractGrant;

class FbExchangeToken extends AbstractGrant
{
    public const NAME = 'fb_exchange_token';

    public function __toString()
    {
        return self::NAME;
    }

    protected function getName(): string
    {
        return self::NAME;
    }

    protected function getRequiredRequestParameters(): array
    {
        return [
            self::NAME,
        ];
    }
}
