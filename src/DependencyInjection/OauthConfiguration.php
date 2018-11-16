<?php

namespace Vairogs\Utils\Oauth\DependencyInjection;

use Vairogs\Utils\DependencyInjection\Component\Definable;
use Vairogs\Utils\DependencyInjection\Component\Configurable;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Vairogs\Utils\Oauth\DependencyInjection\Configurator;

class OauthConfiguration implements Configurable
{
    private const LIBRARIES = [
        Definable::OAUTH => Configurator\OauthConfigurator::class,
        Definable::OPENID => Configurator\OpenidConfigurator::class,
        Definable::OPENIDCONNECT => Configurator\OpenidconnectConfigurator::class,
    ];

    public function configure(ContainerBuilder $container)
    {
        foreach (self::LIBRARIES as $library => $class) {
            if ($class instanceof Configurable) {
                $class->configure($container);
            }
        }
    }
}
