<?php

namespace Gzhegow\Di\Tests\Classes;

use Gzhegow\Di\Di;
use Gzhegow\Di\Domain\Provider\DeferableProviderInterface;


/**
 * BDeferableProvider
 */
class BDeferableProvider implements
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
}
