<?php


namespace Gzhegow\Di\Domain\Provider;

use Gzhegow\Di\Di;


/**
 * AbstractProvider
 */
abstract class AbstractProvider implements ProviderInterface
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
}
