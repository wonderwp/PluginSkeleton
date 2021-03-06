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
            return call_user_func_array([$this, $attributes['action'] . 'Action'], [$attributes]);
        } else {
            return $this->defaultAction($attributes);
        }
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
     * @param  string $viewName
     * @param  array  $params
     *
     * @return \stdClass
     */
    protected function renderPage($viewName, $params)
    {
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
        /** @var ThemeQueryService $qs */
        $qs = wwp_get_theme_service('query');
        $qs->resetPost($post);

        return $post;
    }

    /**
     * @param string $viewName
     * @param array  $params
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
}
