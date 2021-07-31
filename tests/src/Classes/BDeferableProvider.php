<?php

namespace Gzhegow\Di\Tests\Classes;

use Gzhegow\Di\Di;
use Gzhegow\Di\Domain\Provider\DeferableProviderInterface;
use Gzhegow\Di\Domain\Provider\DiscoverableProviderInterface;


/**
 * BDeferableProvider
 */
class BDeferableProvider implements
    DiscoverableProviderInterface,
    DeferableProviderInterface
{
    /**
     * @var Di
     */
    protected $di;


    /**
     * Constructor
     *
     * @param Di $di
     */
    public function __construct(Di $di)
    {
        $this->di = $di;
    }


    /**
     * @return void
     */
    public function boot()
    {
        var_dump('B booted');
    }

    /**
     * @return string[]
     */
    public function provides() : array
    {
        return [
            BInterface::class,
        ];
    }


    /**
     * @return void
     */
    public function register()
    {
        $this->di->singleton(BInterface::class, B::class);
    }


    /**
     * @return string[]
     */
    public function sources() : array
    {
        return [
            0 => __DIR__ . '/../../storage/di/1/1.txt',
            1 => __DIR__ . '/../../storage/di/1/11',
        ];
    }

    /**
     * @return string[]
     */
    public function targets() : array
    {
        return [
            0 => __DIR__ . '/../../storage/di/2/1.txt',
            1 => __DIR__ . '/../../storage/di/2/11',
        ];
    }

    public function path(string $source) : string
    {
        // TODO: Implement path() method.
    }
}
