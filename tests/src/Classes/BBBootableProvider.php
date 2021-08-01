<?php

namespace Gzhegow\Di\Tests\Classes;

use Gzhegow\Di\Di;
use Gzhegow\Di\Domain\Node\Node;
use Gzhegow\Di\Domain\Provider\BootableProviderInterface;


/**
 * BBBootableProvider
 */
class BBBootableProvider implements BootableProviderInterface
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
        var_dump('BB booted');
    }


    /**
     * @return void
     */
    public function register()
    {
        $this->di->factory(BBInterface::class, function (Node $di) {
            $bb = $di->make(BB::class, [
                '$hello' => 1,
                '$world' => 2,
            ]);

            return $bb;
        });
    }
}
