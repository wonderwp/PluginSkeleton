<?php

namespace WonderWp\Component\PluginSkeleton\Controller;

use WonderWp\Component\DependencyInjection\Container;
use WonderWp\Component\HttpFoundation\Request;
use WonderWp\Component\Notification\AdminNotification;
use WonderWp\Component\PluginSkeleton\Exception\MethodNotFoundException;
use WonderWp\Component\PluginSkeleton\ListTable\AbstractListTable;
use WonderWp\Component\PluginSkeleton\ManagerInterface;
use WonderWp\Component\Service\ServiceInterface;

abstract class AbstractPluginBackendController
{
    /** @var ManagerInterface */
    protected $manager;

    /**
     * AbstractPluginBackendController constructor.
     *
     * @param ManagerInterface $manager
     */
    public function __construct(ManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @return string
     */
    public function getRoute()
    {
        $request = Request::getInstance();
        $action  = $request->get('action');

        if ($action !== null) {
            return $action;
        }

        $tabIndex = $request->get('tab', 1);
        $tabs     = $this->getTabs();
        if (array_key_exists($tabIndex, $tabs) && is_array($tabs[$tabIndex]) && array_key_exists('action', $tabs[$tabIndex])) {
            return $tabs[$tabIndex]['action'];
        }

        return $this->getDefaultRoute();
    }

    /**
     * @return string
     */
    public function getDefaultRoute()
    {
        return 'default';
    }

    /**
     * @return void
     */
    public function defaultAction()
    {
        $container = Container::getInstance();
        $prefix    = $this->manager->getConfig('prefix');

        $container
            ->offsetGet('wwp.views.baseAdmin')
            ->registerFrags($prefix)
            ->render([
                'title'   => get_admin_page_title(),
                'tabs'    => $this->getTabs(),
                'content' => "This is your admin controller <strong>defaultAction()</strong> method.<br />You should override it in <strong>" . get_called_class() . "</strong> to display your own admin content as explained in the <a href=\"http://wonderwp.net/Creating_a_plugin/Plugin_architecture/Admin_controller\" target='_blank'>Admin Controller documentation</a>",
            ])
        ;
    }

    /**
     * @return void
     */
    public function route()
    {
        $action = $this->getRoute();
        $this->execRoute($action);
    }

    /**
     * @param string $action
     *
     * @return void
     */
    public function execRoute($action)
    {
        $action .= 'Action';

        if (method_exists($this, $action)) {
            call_user_func([$this, $action]);
        } else {
            throw new MethodNotFoundException("Method $action not found on this controller (" . get_called_class() . ")");
        }
    }

    /**
     * @param AbstractListTable $listTableInstance
     */
    public function listAction(AbstractListTable $listTableInstance = null)
    {
        $container = Container::getInstance();

        if (empty($listTableInstance)) {
            $listTableInstance = $this->manager->getService(ServiceInterface::LIST_TABLE_SERVICE_NAME);
        }

        $listTableInstance = $this->getListTableInstance($listTableInstance);

        $notifications = $this->flashesToNotifications();

        $prefix = $this->manager->getConfig('prefix');
        $container
            ->offsetGet('wwp.views.listAdmin')
            ->registerFrags($prefix)
            ->render([
                'title'             => get_admin_page_title(),
                'tabs'              => $this->getTabs(),
                'listTableInstance' => $listTableInstance,
                'notifications'     => $notifications,
            ])
        ;
    }

    /**
     * @param AbstractListTable|null $listTable
     *
     * @return AbstractListTable
     */
    protected function getListTableInstance(AbstractListTable $listTable = null)
    {
        if ($listTable === null) {
            $listTable = $this->manager->getService(ServiceInterface::LIST_TABLE_SERVICE_NAME);
        }

        if (!$listTable instanceof AbstractListTable) {
            return null;
        }

        if (empty($listTable->getTextDomain()) && !empty($textDomain = $this->manager->getConfig('textDomain'))) {
            $listTable->setTextDomain($textDomain);
        }

        return $listTable;
    }

    /**
     * @return array
     */
    public function getTabs()
    {
        return [];
    }

    /**
     * @return string
     */
    public function getMinCapability()
    {
        return 'read';
    }

    /**
     * @return string[]
     */
    public function flashesToNotifications()
    {
        $request       = Request::getInstance();
        $flashes       = $request->getSession()->getFlashbag()->all();
        $notifications = [];

        foreach ($flashes as $type => $messages) {
            foreach ($messages as $message) {
                $notification    = new AdminNotification($type, $message);
                $notifications[] = $notification->getMarkup();
            }
        }

        return $notifications;
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
