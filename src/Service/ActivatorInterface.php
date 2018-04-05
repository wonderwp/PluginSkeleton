<?php

namespace WonderWp\Component\PluginSkeleton\Service;

use WonderWp\Component\Service\ServiceInterface;

interface ActivatorInterface extends ServiceInterface
{
    /**
     * Code ran upon plugin activation
     *
     * @return static
     */
    public function activate();
}
