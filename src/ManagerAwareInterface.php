<?php

namespace WonderWp\Component\PluginSkeleton;

interface ManagerAwareInterface
{
    public function getManager(): ManagerInterface;
    public function setManager(ManagerInterface $manager): static;
}