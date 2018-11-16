<?php

namespace Vairogs\Utils\Oauth\Provider;

class BaseProvider extends OpenIDConnectProvider
{
    public function getRefreshTokenUrl(): string
    {
        return '';
    }

    public function getRevokeTokenUrl(): string
    {
        return '';
    }

    public function getValidateTokenUrl(): string
    {
        return '';
    }
}
