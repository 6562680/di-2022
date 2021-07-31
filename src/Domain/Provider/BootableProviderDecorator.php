<?php


namespace Gzhegow\Di\Domain\Provider;


/**
 * BootableProviderDecorator
 */
class BootableProviderDecorator extends ProviderDecorator
    implements BootableProviderInterface
{
    /**
     * @return void
     */
    public function boot()
    {
        $this->catch('boot');
    }
}
