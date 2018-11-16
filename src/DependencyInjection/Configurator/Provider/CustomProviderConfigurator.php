<?php

namespace Vairogs\Utils\Oauth\DependencyInjection\Configurator\Provider;

use Vairogs\Utils\Oauth\Client\OAuth2Client;
use Vairogs\Utils\Oauth\Client\Provider\Custom\Custom;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

class CustomProviderConfigurator implements ProviderConfiguratorInterface
{
    public function buildConfiguration(NodeBuilder $node): void
    {
        // @formatter:off
        $node
            ->scalarNode('provider_class')
                ->info('The class name of your provider class (e.g. the one that extends AbstractProvider)')
                ->defaultValue(Custom::class)
            ->end()
            ->scalarNode('client_class')
                ->info('If you have a sub-class of OAuth2Client you want to use, add it here')
                ->defaultValue(OAuth2Client::class)
            ->end()
            ->arrayNode('provider_options')
                ->info('Other options to pass to your provider\'s constructor')
                ->prototype('variable')->end()
            ->end()
        ;
        // @formatter:on
    }

    public function getClientClass(array $config)
    {
        return $config['client_class'];
    }

    public function getProviderClass(array $config)
    {
        return $config['provider_class'];
    }

    public function getProviderDisplayName(): string
    {
        return 'Custom';
    }

    public function getProviderOptions(array $config): array
    {
        return \array_merge([
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
        ], $config['provider_options']);
    }
}
