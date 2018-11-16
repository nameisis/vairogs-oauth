<?php

namespace Vairogs\Utils\Oauth\DependencyInjection\Configurator\Provider;

use Vairogs\Utils\Oauth\Client\Client\TwitterOAuthClient;
use Vairogs\Utils\Oauth\Client\Provider\Twitter\Twitter;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

class TwitterProviderConfigurator implements ProviderConfiguratorInterface
{
    public function buildConfiguration(NodeBuilder $node): void
    {
        // @formatter:off
        $node
            ->scalarNode('client_class')
                ->info('If you have a sub-class of OAuth2Client you want to use, add it here')
                ->defaultValue(TwitterOAuthClient::class)
            ->end()
            ->scalarNode('redirect_route')
                ->isRequired()
                ->cannotBeEmpty()
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

    public function getProviderClass(array $config): string
    {
        return Twitter::class;
    }

    public function getProviderDisplayName(): string
    {
        return 'Twitter';
    }

    public function getProviderOptions(array $config): array
    {
        return \array_merge([
            'clientId' => $config['client_id'],
            'clientSecret' => $config['client_secret'],
            'redirect_route' => $config['redirect_route'],
        ], $config['provider_options']);
    }
}
