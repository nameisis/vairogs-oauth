<?php

namespace Vairogs\Utils\Oauth\DependencyInjection;

use Vairogs\Utils\Core\Exception\VairogsException;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class ClientRegistry
{
    use ContainerAwareTrait;

    /**
     * @var array
     */
    private $serviceMap;

    /**
     * @throws VairogsException
     */
    public function __construct()
    {
        $arguments = \func_get_args();
        $used = [];
        foreach ($arguments as $argument) {
            if (!\is_array($argument)) {
                continue;
            }
            $checkExists = \array_intersect(\array_keys($used), \array_keys($argument));
            $count = \count($checkExists);
            if ($count !== 0) {
                throw new VairogsException(\sprintf('Multiple clients with same key is not allowed! Key'.($count > 1 ? 's' : '').' "%s" appear in configuration more than once!', \implode(',', $checkExists)));
            }
            $used = \array_merge($used, $argument);
        }
        $this->serviceMap = $used;
    }

    /**
     * @param $key
     *
     * @throws VairogsException
     *
     * @return mixed
     */
    public function getClient($key)
    {
        if (!$this->hasClient($key)) {
            throw new VairogsException(\sprintf('Client "%s" not found in registry', $key));
        }

        return $this->container->get($this->serviceMap[$key]['key']);
    }

    public function hasClient($key): bool
    {
        return isset($this->serviceMap[$key]);
    }

    public function getNameByClient($key = ''): string
    {
        if ($key !== '' && $this->hasClient($key)) {
            return $this->serviceMap[$key]['name'];
        }

        return $key;
    }

    public function getClients(): array
    {
        return $this->serviceMap;
    }
}
