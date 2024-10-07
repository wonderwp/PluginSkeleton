<?php

namespace WonderWp\Component\PluginSkeleton;

trait ManagerAwareTrait
{
    /** @var  AbstractManager */
    protected $manager;

    /**
     * @return mixed
     */
    public function getManager(): ManagerInterface
    {
        return $this->manager;
    }

    /**
     * @param mixed $manager
     *
     * @return static
     */
    public function setManager(ManagerInterface $manager): static
    {
        $this->manager = $manager;

        return $this;
    }
}
