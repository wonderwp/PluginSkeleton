<?php

namespace WonderWp\Component\PluginSkeleton;

use WonderWp\Component\Api\ApiServiceInterface;
use WonderWp\Component\Asset\AssetServiceInterface;
use WonderWp\Component\BlockType\Service\BlockTypeServiceInterface;
use WonderWp\Component\CPT\Service\CustomPostTypeServiceInterface;
use WonderWp\Component\CustomFields\Service\CustomFieldsRegistryServiceInterface;
use WonderWp\Component\DependencyInjection\Container;
use WonderWp\Component\Hook\HookServiceInterface;
use WonderWp\Component\HttpFoundation\Request;
use WonderWp\Component\PluginSkeleton\Exception\ControllerNotFoundException;
use WonderWp\Component\PluginSkeleton\Exception\ServiceNotFoundException;
use WonderWp\Component\Routing\Route\RouteServiceInterface;
use WonderWp\Component\Search\Engine\SearchEngineInterface;
use WonderWp\Component\Search\Service\SearchServiceInterface;
use WonderWp\Component\Service\ServiceInterface;
use WonderWp\Component\Shortcode\ShortcodeServiceInterface;
use WonderWp\Component\Task\TaskServiceInterface;
use WonderWp\Component\Taxonomy\Service\TaxonomyServiceInterface;

abstract class AbstractManager implements ManagerInterface
{
    /** @var Container */
    protected $container;
    /** @var Request */
    protected $request;
    /** @var array */
    protected $config = [];
    /** @var callable[] */
    protected $controllers = [];
    /** @var ServiceInterface[]|callable[] */
    protected $services = [];

    const ADMIN_CONTROLLER_TYPE = 'admin';
    const PUBLIC_CONTROLLER_TYPE = 'public';

    /**
     * @param Container $container
     */
    public function __construct(Container $container = null)
    {
        $this->container = $container ?: Container::getInstance();

        if (method_exists($this, 'autoLoad')) {
            user_error('Calling deprecated autoLoad function on ' . static::class, E_USER_DEPRECATED);
            $autoLoader = $this->container['wwp.autoLoader'];
            $this->autoLoad($autoLoader);
        }

        $this->register($this->container);
    }

    /** @inheritdoc */
    public function getConfig($index = null, $default = null)
    {
        if ($index === null) {
            return $this->config;
        }

        return array_key_exists($index, $this->config) ? $this->config[$index] : $default;
    }

    /** @inheritdoc */
    public function setConfig($key, $val = null)
    {
        $this->config[$key] = $val;

        return $this;
    }

    /** @inheritdoc */
    public function getControllers()
    {
        return $this->controllers;
    }

    /** @inheritdoc */
    public function setControllers(array $controllers)
    {
        $this->controllers = $controllers;

        return $this;
    }

    /** @inheritdoc */
    public function addController($controllerType, $controller)
    {
        $this->controllers[$controllerType] = $controller;
    }

    /** @inheritdoc */
    public function getController($controllerType)
    {
        if (!isset($this->controllers[$controllerType])) {
            throw new ControllerNotFoundException("Controller '$controllerType', not found in manager " . get_called_class());
        }

        if (
            !is_object($this->controllers[$controllerType])
            || !method_exists($this->controllers[$controllerType], '__invoke')
        ) {
            return $this->controllers[$controllerType];
        }

        $raw = $this->controllers[$controllerType];
        $controller = $this->controllers[$controllerType] = $raw($this);

        return $controller;
    }

    /** @inheritdoc */
    public function getServices()
    {
        return $this->services;
    }

    /** @inheritdoc */
    public function setServices(array $services)
    {
        $this->services = $services;

        return $this;
    }

    /** @inheritdoc */
    public function addService($serviceType, $service)
    {
        $this->services[$serviceType] = $service;

        return $this;
    }

    /** @inheritdoc */
    public function getService($serviceType)
    {
        if (!array_key_exists($serviceType, $this->services)) {
            throw new ServiceNotFoundException($serviceType, sprintf("Service '%s', not found in manager %s", $serviceType, get_called_class()));
        }

        if (
            !is_object($this->services[$serviceType])
            || !method_exists($this->services[$serviceType], '__invoke')
        ) {
            return $this->services[$serviceType];
        }

        $raw = $this->services[$serviceType];
        $service = $this->services[$serviceType] = $raw($this);

        return $service;
    }

    /** @inheritdoc */
    public function register(Container $container)
    {
        // Register Controllers
        // Register Services
        // Register Configs
    }

    /** @inheritdoc */
    public function run()
    {
        $this->request = Request::getInstance();

        /*
         * Use this hook for example to call some particular services
         */
        do_action('wwp.abstract_manager.run', $this, $this->container);
    }
}
