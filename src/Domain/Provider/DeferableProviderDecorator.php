<?php


namespace Gzhegow\Di\Domain\Provider;


/**
 * DeferableProviderDecorator
 */
class DeferableProviderDecorator extends ProviderDecorator
    implements DeferableProviderInterface
{
    /**
     * @return void
     */
    public function boot()
    {
        $this->catch('boot');
    }

    /**
     * @return array
     */
    public function provides() : array
    {
        return $this->catch('provides') ?? [];
    }
}
