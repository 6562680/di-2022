<?php


namespace Gzhegow\Di\Domain\Provider;


/**
 * AbstractDiscoverableProvider
 */
abstract class AbstractDiscoverableProvider extends AbstractProvider
    implements DiscoverableProviderInterface
{
    /**
     * @param string $source
     *
     * @return string
     */
    public function path(string $source) : string
    {
        $targets = $this->targets();
        $sources = $this->sources();

        $path = null
            ?? ( is_file($targets[ $source ]) ? realpath($targets[ $source ]) : null )
            ?? realpath($sources[ $source ]);

        return $path;
    }
}
