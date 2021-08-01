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
        $path = null;

        $targets = $this->targets();

        if (is_file($targets[ $source ])) {
            $path = realpath($targets[ $source ]);
        }

        if (! $path) {
            $sources = $this->sources();

            $path = realpath($sources[ $source ]);
        }

        return $path;
    }
}
