<?php

declare(strict_types=1);

namespace WonderWp\Component\PluginSkeleton\Tests\Fixtures;

use WonderWp\Component\DependencyInjection\Container;
use WonderWp\Component\PluginSkeleton\AbstractManager;

final class TestableManager extends AbstractManager
{
    public function register(Container $container)
    {
        // Intentionally empty for unit isolation.
    }
}

final class InvokableService
{
    public bool $invoked = false;

    public function __invoke($manager): object
    {
        $this->invoked = true;

        // Return a non-invokable instance so AbstractManager caches it.
        return new \stdClass();
    }
}
