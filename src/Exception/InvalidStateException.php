<?php

namespace Vairogs\Utils\Oauth\Exception;

use RuntimeException;

class InvalidStateException extends RuntimeException implements OAuth2ClientException
{
}
