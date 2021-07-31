<?php


namespace Gzhegow\Di\Domain\Provider;


/**
 * DiscoverableProviderInterface
 */
interface DiscoverableProviderInterface extends ProviderInterface
{
    /**
     * @param string $source
     *
     * @return string
     */
    public function path(string $source) : string;


    /**
     * @return array
     */
    public function sources() : array;

    /**
     * @return array
     */
    public function targets() : array;
}
