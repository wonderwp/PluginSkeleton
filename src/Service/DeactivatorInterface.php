<?php

namespace WonderWp\Component\PluginSkeleton\Service;

use WonderWp\Component\Service\ServiceInterface;

interface DeactivatorInterface extends ServiceInterface
{
    /**
     * Code ran upon plugin deactivation
     *
     * @return static
     */
    public function deactivate();
}
