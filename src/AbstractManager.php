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
         * Call some particular services
         */

        // Hooks
        try {
            $hookService = $this->getService(ServiceInterface::HOOK_SERVICE_NAME);
            if ($hookService instanceof HookServiceInterface) {
                $hookService->register();
            }
        } catch (ServiceNotFoundException $e) {
            if ($e->getServiceType() === ServiceInterface::HOOK_SERVICE_NAME) {
                //No hook service defined, use the default one instead
                $hookService = $this->container['wwp.hook.defaultService'];
                if ($hookService instanceof HookServiceInterface) {
                    if($hookService instanceof ManagerAwareInterface){
                        $hookService->setManager($this);
                    }
                    $hookService->register();
                }
            } else {
                throw $e;
            }
        }

        // Assets
        try {
            $assetService = $this->getService(ServiceInterface::ASSETS_SERVICE_NAME);
            if ($assetService instanceof AssetServiceInterface) {
                $assetManager = $this->container['wwp.asset.manager'];
                $assetManager->addAssetService($assetService);
            }
        } catch (ServiceNotFoundException $e) {
            if ($e->getServiceType() === ServiceInterface::ASSETS_SERVICE_NAME) {
                //No assets service found, nothing to do here for now
            } else {
                throw $e;
            }
        }

        // Routes
        try {
            $routeService = $this->getService(ServiceInterface::ROUTE_SERVICE_NAME);
            if ($routeService instanceof RouteServiceInterface) {
                $router = $this->container['wwp.routes.router'];
                $router->addService($routeService);
            }
        } catch (ServiceNotFoundException $e) {
            if ($e->getServiceType() === ServiceInterface::ROUTE_SERVICE_NAME) {
                //No route service found, nothing to do here for now
            } else {
                throw $e;
            }
        }

        // Apis
        try {
            $apiService = $this->getService(ServiceInterface::API_SERVICE_NAME);
            if ($apiService instanceof ApiServiceInterface) {
                $apiService->registerEndpoints();
            }
        } catch (ServiceNotFoundException $e) {
            if ($e->getServiceType() === ServiceInterface::API_SERVICE_NAME) {
                //No api service found, nothing to do here for now
            } else {
                throw $e;
            }
        }

        // ShortCode
        try {
            $shortCodeService = $this->getService(ServiceInterface::SHORT_CODE_SERVICE_NAME);
            if ($shortCodeService instanceof ShortcodeServiceInterface) {
                $shortCodeService->register();
            }
        } catch (ServiceNotFoundException $e) {
            if ($e->getServiceType() === ServiceInterface::SHORT_CODE_SERVICE_NAME) {
                //No shortcode service found, nothing to do here for now
            } else {
                throw $e;
            }
        }

        // Commands
        try {
            $commandService = $this->getService(ServiceInterface::COMMAND_SERVICE_NAME);
            if ($commandService instanceof TaskServiceInterface) {
                $commandService->register();
            }
        } catch (ServiceNotFoundException $e) {
            if ($e->getServiceType() === ServiceInterface::COMMAND_SERVICE_NAME) {
                //No command service found, nothing to do here for now
            } else {
                throw $e;
            }
        }

        // Search
        try {
            $searchService = $this->getService(ServiceInterface::SEARCH_SERVICE_NAME);
            if ($searchService instanceof SearchServiceInterface) {
                /** @var SearchEngineInterface $searchEngine */
                $searchEngine = $this->container['wwp.search.engine'];
                $searchEngine->addService($searchService);
            }
        } catch (ServiceNotFoundException $e) {
            //No search service found, nothing to do here
            if ($e->getServiceType() === ServiceInterface::SEARCH_SERVICE_NAME) {
                //No search service found, nothing to do here for now
            } else {
                throw $e;
            }
        }

        // Custom Post Types
        try {
            $cptService = $this->getService(ServiceInterface::CUSTOM_POST_TYPE_SERVICE_NAME);
            if ($cptService instanceof CustomPostTypeServiceInterface) {
                $cptService->register();
            }
        } catch (ServiceNotFoundException $e) {
            if ($e->getServiceType() === ServiceInterface::CUSTOM_POST_TYPE_SERVICE_NAME) {
                //No custom post type service found, use the default one instead
                $cptService = $this->container['wwp.cpt.defaultService'];
                if ($cptService instanceof CustomPostTypeServiceInterface) {
                    if($cptService instanceof ManagerAwareInterface){
                        $cptService->setManager($this);
                    }
                    $cptService->register();
                }
            } else {
                throw $e;
            }
        }

        // Taxonomies
        try {
            $taxonomyService = $this->getService(ServiceInterface::TAXONOMY_SERVICE_NAME);
            if ($taxonomyService instanceof TaxonomyServiceInterface) {
                $taxonomyService->register();
            }
        } catch (ServiceNotFoundException $e) {
            if ($e->getServiceType() === ServiceInterface::TAXONOMY_SERVICE_NAME) {
                //No taxonomy service found, use the default one instead
                $taxonomyService = $this->container['wwp.taxonomy.defaultService'];
                if ($taxonomyService instanceof TaxonomyServiceInterface) {
                    if($taxonomyService instanceof ManagerAwareInterface) {
                        $taxonomyService->setManager($this);
                    }
                    $taxonomyService->register();
                }
            } else {
                throw $e;
            }
        }

        //Custom Fields
        try {
            $customFieldService = $this->getService(ServiceInterface::CUSTOM_FIELDS_SERVICE_NAME);
            if ($customFieldService instanceof CustomFieldsRegistryServiceInterface) {
                $customFieldService->register();
            }
        } catch (ServiceNotFoundException $e) {
            if ($e->getServiceType() === ServiceInterface::CUSTOM_FIELDS_SERVICE_NAME) {
                //No custom field service found, use the default one instead
                $customFieldService = $this->container['wwp.customfields.defaultService'];
                if ($customFieldService instanceof CustomFieldsRegistryServiceInterface) {
                    if($customFieldService instanceof ManagerAwareInterface){
                        $customFieldService->setManager($this);
                    }
                    $customFieldService->register();
                }
            } else {
                throw $e;
            }
        }
        
        //Block Types
        try {
            $blockTypeService = $this->getService(ServiceInterface::BLOCK_TYPE_SERVICE_NAME);
            if ($blockTypeService instanceof BlockTypeServiceInterface) {
                $blockTypeService->register();
            }
        } catch (ServiceNotFoundException $e) {
            if ($e->getServiceType() === ServiceInterface::BLOCK_TYPE_SERVICE_NAME) {
                //No block type service found, use the default one instead
                $blockTypeService = $this->container['wwp.blockType.defaultService'];
                if ($blockTypeService instanceof BlockTypeServiceInterface) {
                    if($blockTypeService instanceof ManagerAwareInterface){
                        $blockTypeService->setManager($this);
                    }
                    $blockTypeService->register();
                }
            } else {
                throw $e;
            }
        }

        do_action('wwp.abstract_manager.run', $this);
    }
}
