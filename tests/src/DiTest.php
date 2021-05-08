<?php

namespace Gzhegow\Di\Tests;

use Gzhegow\Support\Fs;
use Gzhegow\Di\DiFactory;
use Gzhegow\Support\Type;
use Gzhegow\Support\Filter;
use Gzhegow\Support\Assert;
use Gzhegow\Di\Domain\Node\Node;
use Gzhegow\Di\Tests\Services\MyAService;
use Gzhegow\Di\Tests\Providers\MyProvider;
use Gzhegow\Di\Tests\Services\Loop\MyLoopService;
use Gzhegow\Di\Tests\Services\Delegate\MyDelegate;
use Gzhegow\Di\Tests\Services\Loop\MyLoopAService;
use Gzhegow\Di\Tests\Services\Loop\MyLoopBService;
use Gzhegow\Di\Tests\Services\MyServiceAInterface;
use Gzhegow\Di\Tests\Providers\MyBootableProvider;
use Gzhegow\Di\Tests\Services\MyBDependsOnAService;
use Gzhegow\Di\Tests\Providers\MyDeferableProvider;
use Gzhegow\Di\Tests\Services\MyCDependsOnABService;
use Gzhegow\Di\Tests\Services\MyDDependsOnAOrNullService;
use Gzhegow\Di\Tests\Services\Delegate\MyDelegateBService;
use Gzhegow\Di\Tests\Services\Delegate\MyDelegateCService;
use Gzhegow\Di\Tests\Services\Loop\MyLoopServiceInterface;
use Gzhegow\Di\Tests\Services\Delegate\MyDelegateAService;
use Gzhegow\Di\Tests\Services\Delegate\MyDelegateDService;
use Gzhegow\Di\Tests\Services\Loop\MyLoopAServiceInterface;
use Gzhegow\Di\Tests\Services\Loop\MyLoopBServiceInterface;
use Gzhegow\Di\Tests\Services\Loop\MyLoopWithInterfaceService;
use Gzhegow\Di\Tests\Services\Loop\MyLoopBWithInterfaceService;
use Gzhegow\Di\Tests\Services\Loop\MyLoopAWithInterfaceService;
use Gzhegow\Di\Exceptions\Runtime\Domain\AutowireLoopException;
use Gzhegow\Di\Tests\Services\Closure\MyClosureServiceBInterface;
use Gzhegow\Di\Tests\Services\Delegate\MyDelegateServiceBInterface;
use Gzhegow\Di\Tests\Services\Delegate\MyDelegateServiceCInterface;
use Gzhegow\Di\Tests\Services\Delegate\MyDelegateServiceDInterface;
use Gzhegow\Di\Tests\Services\Delegate\MyDelegateServiceDependsOnDelegates;

/**
 * DiTest
 */
class DiTest extends AbstractTestCase
{
    public function getFilter() : Filter
    {
        return new Filter();
    }

    public function getType() : Type
    {
        return new Type(
            $this->getFilter()
        );
    }

    public function getFs() : Fs
    {
        return new Fs(
            $this->getFilter(),
            $this->getType()
        );
    }


    public function testNormal()
    {
        $di = ( new DiFactory() )->newDi();
        $di->bind(MyAService::class, MyAService::class);

        $myAService = $di->get(MyAService::class);

        $this->assertInstanceOf(MyAService::class, $myAService);
    }



    public function testNobind()
    {
        $di = ( new DiFactory() )->newDi();

        $myAService = $di->get(MyAService::class);

        $this->assertInstanceOf(MyAService::class, $myAService);
    }



    public function testInterface()
    {
        /** @var MyServiceAInterface $myAService */

        $di = ( new DiFactory() )->newDi();
        $di->bind(MyServiceAInterface::class, MyAService::class);

        $myAService = $di->get(MyServiceAInterface::class);

        $this->assertInstanceOf(MyAService::class, $myAService);
    }



    public function testNullDependency()
    {
        /** @var MyServiceAInterface $myAService */

        $di = ( new DiFactory() )->newDi();

        $myDService = $di->get(MyDDependsOnAOrNullService::class);

        $this->assertInstanceOf(MyDDependsOnAOrNullService::class, $myDService);

        // if you declare dependency with possible null - di ignores dependency and passes null
        $this->assertEquals(null, $myDService->myAService);
    }



    public function testShared()
    {
        $di = ( new DiFactory() )->newDi();
        $di->bindShared(MyAService::class, MyAService::class);

        $myAService1 = $di->get(MyAService::class);
        $myAService2 = $di->get(MyAService::class);

        $this->assertInstanceOf(MyAService::class, $myAService1);
        $this->assertInstanceOf(MyAService::class, $myAService2);
        $this->assertEquals($myAService1, $myAService2);
    }


    public function testSharedClosure()
    {
        $di = ( new DiFactory() )->newDi();
        $di->bindShared(MyClosureServiceBInterface::class, function (Node $loop) {
            return new MyBDependsOnAService(
                $loop->get(MyAService::class)
            );
        });

        $myBClosureSharedService1 = $di->get(MyClosureServiceBInterface::class);
        $myBClosureSharedService2 = $di->get(MyClosureServiceBInterface::class);

        $this->assertEquals($myBClosureSharedService1, $myBClosureSharedService2);
    }



    public function testDelegate()
    {
        /**
         * @var MyDelegate $delegateB
         * @var MyDelegate $delegateC
         * @var MyDelegate $delegateD
         */

        $di = ( new DiFactory() )->newDi();

        $di->setDelegateClass(MyDelegate::class);

        $di->bind(MyDelegateServiceBInterface::class, MyDelegateBService::class);
        $di->bind(MyDelegateServiceCInterface::class, MyDelegateCService::class);
        $di->bind(MyDelegateServiceDInterface::class, MyDelegateDService::class);

        $myService = $di->get(MyDelegateServiceDependsOnDelegates::class);

        $delegateB = $myService->myBService;
        $delegateC = $myService->myCService;
        $delegateD = $myService->myDService;

        $this->assertInstanceOf(MyDelegate::class, $delegateB);
        $this->assertInstanceOf(MyDelegate::class, $delegateC);
        $this->assertInstanceOf(MyDelegate::class, $delegateD);

        $myAService = $myService->myAService;
        $delegateB->property; // __get
        $delegateC->property = 123; // __set
        $invokedDelegate = $delegateD();

        $this->assertInstanceOf(MyDelegateAService::class, $myAService);
        $this->assertInstanceOf(MyDelegateBService::class, $delegateB->getDelegate());
        $this->assertInstanceOf(MyDelegateCService::class, $delegateC->getDelegate());
        $this->assertInstanceOf(MyDelegateDService::class, $delegateD->getDelegate());

        $this->assertEquals($invokedDelegate, $delegateD->getDelegate());
    }



    public function testProvider()
    {
        /** @var MyServiceAInterface $myAService */

        $di = ( new DiFactory() )->newDi();
        $di->registerProvider(MyProvider::class);

        $this->assertInstanceOf(MyAService::class, $myAService = $di->get(MyServiceAInterface::class));
        $this->assertInstanceOf(MyBDependsOnAService::class, $di->get(MyBDependsOnAService::class));

        // boot wont launched
        $this->assertEquals(null, $myAService->getStaticOption());
    }


    public function testProviderBootable()
    {
        /** @var MyServiceAInterface $myAService */

        $di = ( new DiFactory() )->newDi();
        $di->registerProvider(MyBootableProvider::class);

        // remove old sync before test
        $fs = $this->getFs();
        $fs->rmdir(__DIR__ . '/../config/dest', true);

        $myAService = $di->get(MyServiceAInterface::class);

        // bont still not launched
        $this->assertEquals(null, $myAService->getDynamicOption()); // registered, not booted
        $this->assertEquals(null, $myAService->getStaticOption());  // registered, not booted

        // triggering boot - that synces configs, resources and calls all providers boot method
        $di->boot();

        // singleton
        $this->assertEquals(1, $myAService->getStaticOption());

        // instance
        $this->assertEquals(null, $myAService->getDynamicOption());

        // check syncing is correct
        $this->assertFileExists(__DIR__ . '/../config/dest/file.conf');
        $this->assertFileExists(__DIR__ . '/../config/dest/dir/file.conf');
        $this->assertFileExists(__DIR__ . '/../config/dest/dir/dir/file.conf');
    }


    public function testProviderDeferable()
    {
        /** @var MyServiceAInterface $myAService */

        $di = ( new DiFactory() )->newDi();
        $di->registerProvider(MyDeferableProvider::class);

        $myAService = $di->get(MyServiceAInterface::class);

        // result
        $this->assertEquals(null, $myAService->getDynamicOption());
        $this->assertEquals(1, $myAService->getStaticOption()); // booted on create, so - already booted

        // nothing happens, because boot already done for this service on create
        $di->boot();

        // expected same result
        $this->assertEquals(null, $myAService->getDynamicOption());
        $this->assertEquals(1, $myAService->getStaticOption());
    }


    public function testCall()
    {
        /** @var MyServiceAInterface $myAService */

        $di = ( new DiFactory() )->newDi();
        $di->bind(MyServiceAInterface::class, MyAService::class);

        $decorationService = $myAService = $di->get(MyServiceAInterface::class);

        $testCase = $this;
        $di->call($myAService, function (MyServiceAInterface $myAService) use ($decorationService, $testCase) {
            $testCase->assertEquals($decorationService, $this);
            $testCase->assertEquals($decorationService, $myAService);

            // dynamicOption is protected, call() method changes bounded context
            // to allow make properties readonly filled with factory/builder classes without creating public setters/getters
            $myAService = $this;
            $myAService->dynamicOption = 123;
        });

        static::assertEquals(123, $myAService->getDynamicOption());
    }



    public function testHandle()
    {
        /** @var MyServiceAInterface $testService */

        $di = ( new DiFactory() )->newDi();
        $di->bind(MyServiceAInterface::class, MyAService::class);

        $decorationService = $testService = $di->get(MyServiceAInterface::class);

        $data = [
            MyServiceAInterface::class => $decorationService,

            '$var2' => 'world',
            '$var5' => 'foo',

            0 => 'hello',
        ];

        $di->handle(function (
            $var,
            $var2,
            $var3 = 'bar',
            $var4 = null,
            MyServiceAInterface $service = null
        ) use ($decorationService) {
            static::assertEquals($decorationService, $service);
            static::assertEquals([
                0 => 'hello', // passed as int argument without ordering
                1 => 'world', // passed as string argument
                2 => 'bar', // default value
                3 => null, // default null
                4 => $decorationService, // created by interface

                // 5 => 'foo', // ignored because no param match
            ], func_get_args());
        }, $data);
    }

    public function testHandleNoArguments()
    {
        /** @var MyServiceAInterface $testService */

        $di = ( new DiFactory() )->newDi();
        $di->bind(MyServiceAInterface::class, MyAService::class);

        $service = $di->get(MyServiceAInterface::class);

        $data = [
            MyServiceAInterface::class => $service, // will be ignored - no arguments match
        ];

        $di->handle(function () {
            static::assertEquals([], func_get_args());
        }, $data);
    }


    public function testHandleVariadic()
    {
        /** @var MyServiceAInterface $testService */

        $di = ( new DiFactory() )->newDi();
        $di->bind(MyServiceAInterface::class, MyAService::class);

        $data = [
            0        => null,
            2        => null,
            '$world' => 'world1',
            '$args'  => [],
        ];

        $di->handle(function ($hello, $world = null, ...$args) {
            static::assertEquals([
                0 => null, // passed by name
                1 => 'world1', // passed by name

                // 2 => [], // variadic parameters becomes [] by default, even if null or empty array is passed
            ], func_get_args());
        }, $data);
    }

    public function testHandleUnexpectedOrder()
    {
        /** @var MyServiceAInterface $testService */

        $di = ( new DiFactory() )->newDi();
        $di->bind(MyServiceAInterface::class, MyAService::class);

        $myAService = $di->get(MyServiceAInterface::class);

        $data = [
            '$var2' => '456',
            0       => '123',
        ];

        $di->handle(function ($var1, MyServiceAInterface $myService, $var2) use ($myAService) {
            static::assertEquals([
                0 => '123',
                1 => $myAService, // array was expanded with autowired dependency
                2 => '456',
            ], func_get_args());
        }, $data);
    }


    public function testSame()
    {
        // both of dependent services required same service, its normal behavior

        $di = ( new DiFactory() )->newDi();
        $instance = $di->get(MyCDependsOnABService::class);

        $this->assertInstanceOf(MyCDependsOnABService::class, $instance);
    }


    public function testBindSelf()
    {
        // service bounded as self, its normal behavior (usually for singletons)

        $di = ( new DiFactory() )->newDi();
        $di->bindShared(MyBDependsOnAService::class, MyBDependsOnAService::class);
        $di->bind(MyCDependsOnABService::class, MyCDependsOnABService::class);

        $b = $di->get(MyBDependsOnAService::class);
        $c = $di->get(MyCDependsOnABService::class);

        $this->assertInstanceOf(MyBDependsOnAService::class, $b);
        $this->assertInstanceOf(MyCDependsOnABService::class, $c);
    }



    public function testBadLoopSelf()
    {
        // service requires itself

        $this->expectException(AutowireLoopException::class);

        $di = ( new DiFactory() )->newDi();
        $di->get(MyLoopService::class);
    }

    public function testBadLoopSelfInterface()
    {
        // service requires itself via interface

        $this->expectException(AutowireLoopException::class);

        $di = ( new DiFactory() )->newDi();
        $di->bind(MyLoopServiceInterface::class, MyLoopWithInterfaceService::class);

        $di->get(MyLoopWithInterfaceService::class);
    }

    public function testBadLoopSelfClosure()
    {
        // service requires itself inside closure factory

        $this->expectException(AutowireLoopException::class);

        $di = ( new DiFactory() )->newDi();
        $di->bind(MyLoopService::class, function (Node $loop) {
            return $loop->create(MyLoopService::class);
        });

        $di->get(MyLoopService::class);
    }


    public function testBadLoopAB()
    {
        // service A requires B, and service B requires service A

        $this->expectException(AutowireLoopException::class);

        $di = ( new DiFactory() )->newDi();
        $di->get(MyLoopAService::class);
    }

    public function testBadLoopABInterface()
    {
        // service A requires B, and service B requires service A via interfaces

        $this->expectException(AutowireLoopException::class);

        $di = ( new DiFactory() )->newDi();
        $di->bind(MyLoopAServiceInterface::class, MyLoopAWithInterfaceService::class);
        $di->bind(MyLoopBServiceInterface::class, MyLoopBWithInterfaceService::class);

        $di->get(MyLoopAServiceInterface::class);
    }

    public function testBadLoopCrossReference()
    {
        $this->expectException(AutowireLoopException::class);

        $di = ( new DiFactory() )->newDi();
        $di->bind(MyLoopAService::class, MyLoopBService::class); // a to b
        $di->bind(MyLoopBService::class, MyLoopAService::class); // b to a

        $di->get(MyLoopAService::class);
    }

    public function testBadLoopCrossReferenceInterfaces()
    {
        $this->expectException(AutowireLoopException::class);

        $di = ( new DiFactory() )->newDi();
        $di->bind(MyLoopAServiceInterface::class, MyLoopBWithInterfaceService::class); // a to b
        $di->bind(MyLoopBServiceInterface::class, MyLoopAWithInterfaceService::class); // b to a

        $di->get(MyLoopAServiceInterface::class);
    }



    public function testBadLoopABClosure()
    {
        // use $parent->get($id) instead of $di->get($id) to prevent breaking autowire-loop detection

        $this->expectException(AutowireLoopException::class);

        $di = ( new DiFactory() )->newDi();

        $di->bind(MyLoopAService::class, function (Node $parent) {
            return new MyLoopAService(
                $parent->create(MyLoopBService::class)
            );
        });

        $di->bind(MyLoopBService::class, function (Node $parent) {
            return new MyLoopBService(
                $parent->create(MyLoopAService::class)
            );
        });

        $di->get(MyLoopAService::class);
    }

    public function testBadLoopABClosureInterfaces()
    {
        // use $parent->get($id) instead of $di->get($id) to prevent breaking autowire-loop detection

        $this->expectException(AutowireLoopException::class);

        $di = ( new DiFactory() )->newDi();

        $di->bind(MyLoopAServiceInterface::class, function (Node $parent) {
            return new MyLoopAService(
                $parent->create(MyLoopBServiceInterface::class)
            );
        });

        $di->bind(MyLoopBServiceInterface::class, function (Node $parent) {
            return new MyLoopBService(
                $parent->create(MyLoopAServiceInterface::class)
            );
        });

        $di->get(MyLoopAServiceInterface::class);
    }
}
