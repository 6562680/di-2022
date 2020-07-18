<?php

namespace Tests;

use Gzhegow\Di\Di;
use Tests\Services\MyAService;
use Tests\Services\MyCService;
use PHPUnit\Framework\TestCase;
use Tests\Providers\MyProvider;
use Tests\Services\MyLoopService;
use Tests\Services\MyLoopAService;
use Tests\Services\MyServiceInterface;
use Tests\Providers\MyBootableProvider;
use Tests\Providers\MyDeferableProvider;
use Gzhegow\Di\Exceptions\Runtime\AutowireException;

/**
 * Class Test
 */
class Test extends TestCase
{
	/**
	 * @return void
	 */
	public function testDecorate()
	{
		/** @var MyServiceInterface $testService */

		$case = $this;

		$di = new Di();
		$di->bind(MyServiceInterface::class, MyAService::class);

		$decorationService = $testService = $di->getOrFail(MyServiceInterface::class);

		$di->call($testService, function (MyServiceInterface $testService) use ($case, $decorationService) {
			$case->assertEquals($decorationService, $this);
			$case->assertEquals($decorationService, $testService);

			// dynamicOption is protected, we used dynamic this
			// to allow make properties readonly filled with factory/builder classes
			$testService = $this;
			$testService->dynamicOption = 123;
		});

		$this->assertEquals(123, $testService->getDynamicOption());
	}


	/**
	 * @return void
	 */
	public function testPass()
	{
		/** @var MyServiceInterface $testService */

		$case = $this;

		$di = new Di();
		$di->bind(MyServiceInterface::class, MyAService::class);

		$decorationService = $testService = $di->getOrFail(MyServiceInterface::class);

		$data = [
			MyServiceInterface::class => $decorationService,

			'$var2' => 'world',
			'$var5' => 'foo',

			0 => 'hello',
		];

		$di->handle(function (
			$var,
			$var2,
			$var3 = 'bar',
			$var4 = null,
			MyServiceInterface $service = null
		) use (
			$case,
			$decorationService
		) {
			$case->assertEquals([
				0 => 'hello', // passed as int argument without ordering
				1 => 'world', // passed as string argument
				2 => 'bar', // default value
				3 => null, // default null
				4 => $decorationService, // created by interface

				// 5 => 'foo', // ignored because no param match
			], func_get_args());

			$case->assertEquals($decorationService, $service);
		}, $data);
	}

	/**
	 * @return void
	 */
	public function testPassVariadic()
	{
		/** @var MyServiceInterface $testService */

		$case = $this;

		$di = new Di();
		$di->bind(MyServiceInterface::class, MyAService::class);

		$data = [
			0        => null,
			2        => null,
			'$world' => 'world1',
			'$args'  => [],
		];

		$di->handle(function ($hello, $world = null, ...$args) use ($case) {
			$case->assertEquals([
				0 => null, // passed by name
				1 => 'world1', // passed by name
				// 2 => [], // variadic parameters becomes [] by default, even if null or empty array is passed
			], func_get_args());
		}, $data);
	}

	/**
	 * @return void
	 */
	public function testPassNoArguments()
	{
		/** @var MyServiceInterface $testService */

		$case = $this;

		$di = new Di();
		$di->bind(MyServiceInterface::class, MyAService::class);

		$service = $di->getOrFail(MyServiceInterface::class);

		$data = [
			MyServiceInterface::class => $service, // will be ignored - no arguments match
		];

		$di->handle(function () use ($case, $service) {
			$case->assertEquals([], func_get_args());
		}, $data);
	}

	/**
	 * @return void
	 */
	public function testPassUnexpectedOrder()
	{
		/** @var MyServiceInterface $testService */

		$case = $this;

		$di = new Di();
		$di->bind(MyServiceInterface::class, MyAService::class);

		$myAService = $di->getOrFail(MyServiceInterface::class);

		$data = [
			'$var2' => '456',
			0       => '123',
		];

		$di->handle(function ($var1, MyServiceInterface $myService, $var2) use ($case, $myAService) {
			$case->assertEquals([
				0 => '123',
				1 => $myAService, // array was expanded with autowired dependency
				2 => '456',
			], func_get_args());
		}, $data);
	}


	/**
	 * @return void
	 */
	public function testNormal()
	{
		/** @var MyServiceInterface $testService */

		$di = new Di();
		$di->registerProvider(MyProvider::class);

		$testService = $di->getOrFail(MyServiceInterface::class);

		$this->assertEquals(null, $testService->getStaticOption());
	}

	/**
	 * @return void
	 */
	public function testBootable()
	{
		/** @var MyServiceInterface $testService */

		$di = new Di();
		$di->registerProvider(MyBootableProvider::class);

		$testService = $di->getOrFail(MyServiceInterface::class);

		$this->assertEquals(null, $testService->getDynamicOption()); // registered, not booted
		$this->assertEquals(null, $testService->getStaticOption());  // registered, not booted

		$di->boot();

		$this->assertEquals(null, $testService->getDynamicOption()); // not a singleton
		$this->assertEquals(1, $testService->getStaticOption());     // shared for all classes
	}

	/**
	 * @return void
	 */
	public function testDeferable()
	{
		/** @var MyServiceInterface $testService */

		$di = new Di();
		$di->registerProvider(MyDeferableProvider::class);

		$testService = $di->getOrFail(MyServiceInterface::class);

		$this->assertEquals(null, $testService->getDynamicOption());
		$this->assertEquals(1, $testService->getStaticOption()); // booted on create, so - already booted

		$di->boot(); // nothing happens, because of deferable boot

		$this->assertEquals(null, $testService->getDynamicOption());
		$this->assertEquals(1, $testService->getStaticOption()); // same result
	}


	/**
	 * @return void
	 */
	public function testSame()
	{
		// both of dependent services required same service, its normal behavior

		$di = new Di();
		$instance = $di->getOrFail(MyCService::class);

		$this->assertInstanceOf(MyCService::class, $instance);
	}


	/**
	 * @return void
	 */
	public function testBadLoop()
	{
		// service requires itself
		$this->expectException(AutowireException::class);

		$di = new Di();
		$di->getOrFail(MyLoopService::class);
	}

	/**
	 * @return void
	 */
	public function testBadLoopAB()
	{
		// service A requires B, and service B requires service A
		$this->expectException(AutowireException::class);

		$di = new Di();
		$di->getOrFail(MyLoopAService::class);
	}
}