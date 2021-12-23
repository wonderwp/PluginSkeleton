<?php

namespace WonderWp\Component\PluginSkeleton\Controller;

use WonderWp\Component\DependencyInjection\Container;
use WonderWp\Component\HttpFoundation\Request;
use WonderWp\Component\PluginSkeleton\ManagerInterface;
use function WonderWp\Functions\get_plugin_file;
use WonderWp\Theme\Core\Service\ThemeQueryService;
use WonderWp\Component\PluginSkeleton\Exception\ViewNotFoundException;

abstract class AbstractPluginFrontendController
{
    /** @var Container */
    protected $container;
    /** @var ManagerInterface */
    protected $manager;
    /** @var Request */
    protected $request;

    /**
     * AbstractPluginFrontendController constructor.
     *
     * @param ManagerInterface $manager
     */
    public function __construct(ManagerInterface $manager)
    {
        $this->manager   = $manager;
        $this->container = Container::getInstance();
        $this->request   = Request::getInstance();
    }

    /**
     * @param array $attributes
     *
     * @return string
     */
    public function handleShortCode(array $attributes = [])
    {
        if (!empty($attributes['action']) && method_exists($this, $attributes['action'] . 'Action')) {
            $actionName = $attributes['action'];
        } else {
            $actionName = 'default';
        }
        $actionName = apply_filters('frontendController.handleShortcode.actionName', $actionName, $this, $attributes);
        return call_user_func_array([$this, $actionName . 'Action'], [$attributes]);
    }

    /**
     * @param array $attributes
     *
     * @return string
     */
    public function defaultAction(array $attributes = [])
    {
        return '';
    }

    /**
     * Render view as a full page.
     *
     * @param string $viewName
     * @param array $params
     *
     * @return \stdClass
     */
    protected function renderPage($viewName, $params)
    {
        global $wp_query, $post;
        $post             = new \stdClass();
        $title            = $params['title'];
        $post->post_title = $title;
        $post->post_name  = sanitize_title($title);

        $post->ID           = 0;
        $post->post_content = $this->renderView($viewName, $params);

        if (!empty($params['image'])) {
            $post->post_featured_image = $params['image'];
        }
        if (!empty($params['excerpt'])) {
            $post->post_excerpt = $params['excerpt'];
        }

        if (isset($params['metas'])) {
            $post->metas = $params['metas'];
        }

        $wp_query->is_home = false;

        if (!isset($post->post_parent)) {
            $post->post_parent = 0;
        }
        if (!isset($post->metas)) {
            $post->metas = [];
        }

        $wp_query->posts          = [0 => $post];
        $wp_query->queried_object = $post;
        $wp_query->post_count     = 1;

        return $post;
    }

    /**
     * @param string $viewName
     * @param array $params
     *
     * @return string
     */
    public function renderView($viewName, array $params = [])
    {
        $viewContent = '';

        $viewFile = $this->locateView($viewName);

        if (file_exists($viewFile)) {
            ob_start();
            // Spread attributes
            extract($params);
            include $viewFile;

            $viewContent = ob_get_clean();
        } else {
            throw new ViewNotFoundException("View $viewName not found. Tried locating at " . $viewFile);
        }

        return $viewContent;
    }

    public function locateView($viewName)
    {
        $pluginRoot = $this->manager->getConfig('path.root');

        $viewFile = '';

        if (!empty($pluginRoot)) {
            $viewFile = get_plugin_file($pluginRoot, DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $viewName . '.php');
            if (!file_exists($viewFile)) {
                $viewFile = $pluginRoot . '/public/views/' . $viewName . '.php';
            }
        }

        return $viewFile;
    }

    /**
     * @return ManagerInterface
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * @param ManagerInterface $manager
     */
    public function setManager($manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param array $attributes
     * @return null
     */
    public function voidAction(array $attributes = [])
    {
        return '';
    }
}
