<?php

namespace Gzhegow\Di\Tests\Classes;

use Gzhegow\Di\Di;
use Gzhegow\Di\Domain\Node\Node;
use Gzhegow\Di\Domain\Provider\BootableProviderInterface;
use Gzhegow\Di\Domain\Provider\AbstractDiscoverableProvider;


/**
 * BBBootableProvider
 */
class BBBootableProvider extends AbstractDiscoverableProvider implements
    BootableProviderInterface
{
    /**
     * Constructor
     *
     * @param Di $di
     */
    public function __construct(Di $di)
    {
        parent::__construct($di);
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
        $this->di->factory(BBInterface::class, function (Node $current) {
            $config = require $this->path('config');

            $bb = $current->make(BB::class, [
                '$hello'  => 1,
                '$world'  => 2,
                '$config' => $config,
            ]);

            return $bb;
        });
    }


    /**
     * @return string[]
     */
    public function sources() : array
    {
        return [
            'config'    => __DIR__ . '/../../storage/di/demo/config.php',
            'directory' => __DIR__ . '/../../storage/di/demo/directory',
        ];
    }

    /**
     * @return string[]
     */
    public function targets() : array
    {
        return [
            'config'    => __DIR__ . '/../../storage/di/demo2/config.php',
            'directory' => __DIR__ . '/../../storage/di/demo2/directory',
        ];
    }
}
