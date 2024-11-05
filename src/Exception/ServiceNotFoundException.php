<?php

namespace WonderWp\Component\PluginSkeleton\Exception;

class ServiceNotFoundException extends \Exception
{
    protected string $serviceType;

    public function __construct(string $serviceType, string $message = '', int $code = 0, \Throwable $previous = null)
    {
        $this->serviceType = $serviceType;
        parent::__construct($message, $code, $previous);
    }

    public function getServiceType(): string
    {
        return $this->serviceType;
    }

    public function setServiceType(string $serviceType): ServiceNotFoundException
    {
        $this->serviceType = $serviceType;
        return $this;
    }

}
