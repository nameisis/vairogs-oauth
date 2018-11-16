<?php

namespace Vairogs\Utils\Oauth\Component;

interface Loginable
{
    public function urlPath($return);

    public function validate();

    public function redirect();

    public function fetchUser();
}
