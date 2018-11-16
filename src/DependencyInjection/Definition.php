<?php

namespace Vairogs\Utils\Oauth\DependencyInjection;

use Vairogs\Utils\DependencyInjection\Component\Definable;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class Definition implements Definable
{
    private const ALLOWED = [
        Definable::OAUTH,
        Definable::OPENID,
        Definable::OPENIDCONNECT,
    ];

    public function getExtensionDefinition($extension): ArrayNodeDefinition
    {
        if (!\in_array($extension, self::ALLOWED, true)) {
            throw new InvalidConfigurationException(\sprintf('Invalid extension: %s', $extension));
        }

        switch ($extension) {
            case Definable::OAUTH:
                return $this->getOauthDefinition();
            case Definable::OPENID:
                return $this->getOpenidDefinition();
            case Definable::OPENIDCONNECT:
                return $this->getOpenidconnectDefinition();
        }
    }

    private function getOauthDefinition(): ArrayNodeDefinition
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root(Definable::OAUTH);
        /** @var ArrayNodeDefinition $node */

        // @formatter:off
        $node
            ->canBeEnabled()
            ->addDefaultsIfNotSet()
            ->children()

            ->end();
        // @formatter:on

        return $node;
    }

    private function getOpenidDefinition(): ArrayNodeDefinition
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root(Definable::OPENID);
        /** @var ArrayNodeDefinition $node */

        // @formatter:off
        $node
            ->canBeEnabled()
            ->addDefaultsIfNotSet()
            ->children()

            ->end();
        // @formatter:on

        return $node;
    }

    private function getOpenidconnectDefinition(): ArrayNodeDefinition
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root(Definable::OPENIDCONNECT);
        /** @var ArrayNodeDefinition $node */

        // @formatter:off
        $node
            ->canBeEnabled()
            ->addDefaultsIfNotSet()
            ->children()

            ->end();
        // @formatter:on

        return $node;
    }
}
