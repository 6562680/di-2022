<?php


namespace Gzhegow\Di\Domain\Provider;


/**
 * BootingProviderInterface
 */
interface BootingProviderInterface extends ProviderInterface
{
    /**
     * @return void
     */
    public function boot();
}
