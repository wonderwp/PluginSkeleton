<?php

declare(strict_types=1);

namespace WonderWp\Component\PluginSkeleton\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WonderWp\Component\DependencyInjection\Container;
use WonderWp\Component\PluginSkeleton\Exception\ControllerNotFoundException;
use WonderWp\Component\PluginSkeleton\Exception\ServiceNotFoundException;
use WonderWp\Component\PluginSkeleton\Tests\Fixtures\InvokableService;
use WonderWp\Component\PluginSkeleton\Tests\Fixtures\TestableManager;

final class AbstractManagerTest extends TestCase
{
    private TestableManager $manager;

    protected function setUp(): void
    {
        Container::resetInstance();
        $this->manager = new TestableManager(new Container());
        $GLOBALS['wwp_test_do_action'] = [];
    }

    public function testHasServiceFalseThenTrue(): void
    {
        $this->assertFalse($this->manager->hasService('missing'));
        $this->manager->addService('svc', new \stdClass());
        $this->assertTrue($this->manager->hasService('svc'));
    }

    public function testGetServiceReturnsRegisteredInstance(): void
    {
        $svc = new \stdClass();
        $this->manager->addService('svc', $svc);
        $this->assertSame($svc, $this->manager->getService('svc'));
    }

    public function testGetServiceThrowsWhenMissing(): void
    {
        $this->expectException(ServiceNotFoundException::class);
        $this->manager->getService('nope');
    }

    public function testGetServiceInvokesLazyFactoryOnce(): void
    {
        $factory = new InvokableService();
        $this->manager->addService('lazy', $factory);

        $first = $this->manager->getService('lazy');
        $second = $this->manager->getService('lazy');

        $this->assertTrue($factory->invoked);
        $this->assertInstanceOf(\stdClass::class, $first);
        $this->assertSame($first, $second);
    }

    public function testGetControllerThrowsWhenMissing(): void
    {
        $this->expectException(ControllerNotFoundException::class);
        $this->manager->getController('admin');
    }

    public function testGetControllerReturnsRegisteredInstance(): void
    {
        $controller = new \stdClass();
        $this->manager->addController('public', $controller);
        $this->assertSame($controller, $this->manager->getController('public'));
    }

    public function testConfigRoundTrip(): void
    {
        $this->manager->setConfig('prefix', 'demo');
        $this->assertSame('demo', $this->manager->getConfig('prefix'));
        $this->assertSame('fallback', $this->manager->getConfig('missing', 'fallback'));
    }

    public function testRunFiresAction(): void
    {
        $this->manager->run();
        $this->assertNotEmpty($GLOBALS['wwp_test_do_action']);
        $this->assertSame('wwp.abstract_manager.run', $GLOBALS['wwp_test_do_action'][0]['hook']);
    }
}
