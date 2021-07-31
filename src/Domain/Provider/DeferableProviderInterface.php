<?php


namespace Gzhegow\Di\Domain\Provider;


/**
 * DeferableProviderInterface
 */
interface DeferableProviderInterface extends BootingProviderInterface
{
    /**
     * @return array
     */
    public function provides() : array;
}
