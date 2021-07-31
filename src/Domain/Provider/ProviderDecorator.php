<?php


namespace Gzhegow\Di\Domain\Provider;

use Gzhegow\Di\Di;


/**
 * Provider
 */
class ProviderDecorator extends AbstractDiscoverableProvider implements
    DiscoverableProviderInterface
{
    /**
     * @var object
     */
    protected $provider;


    /**
     * Constructor
     *
     * @param Di     $di
     *
     * @param object $provider
     */
    public function __construct(Di $di,
        object $provider
    )
    {
        $this->provider = $provider;

        parent::__construct($di);
    }


    /**
     * @return void
     */
    public function register()
    {
        $this->catch('register');
    }


    /**
     * @return array
     */
    public function sources() : array
    {
        return $this->catch('sources') ?? [];
    }

    /**
     * @return array
     */
    public function targets() : array
    {
        return $this->catch('targets') ?? [];
    }


    /**
     * @param string $method
     * @param array  $params
     *
     * @return null|mixed
     */
    protected function catch(string $method, array $params = [])
    {
        $result = null;

        try {
            $result = call_user_func_array([ $this->provider, $method ], $params);
        }
        catch ( \Throwable $e ) {
        }

        return $result;
    }
}
