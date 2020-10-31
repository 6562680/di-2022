<?php

namespace Gzhegow\Di\Tests\Providers;

use Gzhegow\Di\BootableProvider;
use Gzhegow\Di\Tests\Services\MyAService;
use Gzhegow\Di\Tests\Services\MyServiceAInterface;

/**
 * Class MyBootableProvider
 */
class MyBootableProvider extends BootableProvider
{
	/**
	 * @return MyServiceAInterface
	 */
	protected function getMyAService() : MyServiceAInterface
	{
		return $this->di->getOrFail(MyServiceAInterface::class);
	}


	/**
	 * @return void
	 */
	public function register() : void
	{
		$this->di->bind(MyServiceAInterface::class, MyAService::class);
	}

	/**
	 * @return void
	 */
	public function boot() : void
	{
		require $this->syncRealpath('bootstrap');

		$myAService = $this->getMyAService();

		$myAService::setStaticOption(1);
		$myAService->setDynamicOption(2);
	}


	/**
	 * @return array
	 */
	protected function define() : array
	{
		return [
			'bootstrap' => __DIR__ . '/../../config/src/bootstrap.php',
			'config'    => __DIR__ . '/../../config/src/file.conf',
			'resources' => __DIR__ . '/../../config/src/dir',
		];
	}

	/**
	 * @return array
	 */
	protected function sync() : array
	{
		return [
			'bootstrap' => __DIR__ . '/../../config/dest/bootstrap.php',
			'config'    => __DIR__ . '/../../config/dest/file.conf',
			'resources' => __DIR__ . '/../../config/dest/dir',
		];
	}
}