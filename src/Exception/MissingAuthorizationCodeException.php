<?php

namespace Vairogs\Utils\Oauth\Exception;

use RuntimeException;

class MissingAuthorizationCodeException extends RuntimeException implements OAuth2ClientException
{
}
