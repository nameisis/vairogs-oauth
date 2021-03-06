<?php

namespace Vairogs\Utils\Oauth\Component;

interface SocialUserInterface
{
    public function getId();

    public function getEmail();

    public function getFirstName();

    public function getLastName();

    public function getUsername();
}
