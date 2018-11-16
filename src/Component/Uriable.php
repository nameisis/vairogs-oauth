<?php

namespace Vairogs\Utils\Oauth\Component;

interface Uriable
{
    public function getUrl();

    public function redirect();
}
