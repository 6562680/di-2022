<?php

namespace Gzhegow\Di;

use Gzhegow\Di\Domain\Node\Node;
use Gzhegow\Di\Domain\Provider\ProviderInterface;
use Gzhegow\Di\Domain\Provider\ProviderDecorator;
use Gzhegow\Di\Exceptions\Runtime\NotFoundException;
use Gzhegow\Di\Exceptions\Runtime\AutowireException;
use Gzhegow\Di\Exceptions\Runtime\FilesystemException;
use Gzhegow\Di\Domain\Provider\BootingProviderInterface;
use Gzhegow\Di\Exceptions\Logic\InvalidArgumentException;
use Gzhegow\Di\Domain\Provider\BootableProviderInterface;
use Gzhegow\Di\Domain\Provider\BootableProviderDecorator;
use Gzhegow\Di\Domain\Provider\DeferableProviderInterface;
use Gzhegow\Di\Domain\Provider\DeferableProviderDecorator;
use Gzhegow\Di\Domain\Provider\DiscoverableProviderInterface;


/**
 * Di
 */
class Di implements DiInterface
{
    /**
     * @var bool
     */
    protected $booted = false;
    /**
     * @var bool
     */
    protected $discovered = false;

    /**
     * @var array
     */
    protected $items = [];
    /**
     * @var string[]
     */
    protected $binds = [];
    /**
     * @var callable[]
     */
    protected $factories = [];

    /**
     * @var bool[]
     */
    protected $shared = [];

    /**
     * @var string[][]
     */
    protected $tags = [];
    /**
     * @var string[][]|DeferableProviderInterface[][]
     */
    protected $deferables = [];

    /**
     * @var string[]
     */
    protected $aware = [];
    /**
     * @var callable[][]
     */
    protected $extends = [];
    /**
     * @var callable[][]
     */
    protected $extendTags = [];

    /**
     * @var ProviderInterface[]
     */
    protected $providers = [];
    /**
     * @var bool[]|BootingProviderInterface[]
     */
    protected $providersBooted = [];
    /**
     * @var bool[]|DiscoverableProviderInterface[]
     */
    protected $providersDiscovered = [];

    /**
     * @var bool[]|BootableProviderInterface[]
     */
    protected $providersBootable = [];
    /**
     * @var bool[]|DeferableProviderInterface[]
     */
    protected $providersDeferable = [];
    /**
     * @var bool[]|DiscoverableProviderInterface[]
     */
    protected $providersDiscovarable = [];


    /**
     * @return static
     */
    public function boot()
    {
        if ($this->booted) {
            return $this;
        }

        $this->booted = true;

        foreach ( $this->providersBootable as $providerClass => $bool ) {
            /** @var BootableProviderInterface $provider */

            $provider = $this->providers[ $providerClass ];

            $this->bootProvider($provider);
        }

        return $this;
    }

    /**
     * @param BootingProviderInterface $provider
     *
     * @return static
     */
    protected function bootProvider(BootingProviderInterface $provider)
    {
        if (! empty($this->providersBooted[ $providerClass = get_class($provider) ])) {
            return $this;
        }

        $provider->boot();

        $this->providersBooted[ $providerClass ] = true;

        return $this;
    }


    /**
     * @param string $abstract
     *
     * @return null|mixed
     */
    public function isStored(string $abstract)
    {
        return $this->items[ $abstract ] ?? null;
    }

    /**
     * @param string $abstract
     *
     * @return null|string
     */
    public function isBound(string $abstract)
    {
        return $this->binds[ $abstract ] ?? null;
    }

    /**
     * @param string $abstract
     *
     * @return null|callable
     */
    public function isBlueprinted(string $abstract)
    {
        return $this->factories[ $abstract ] ?? null;
    }

    /**
     * @param string $abstract
     *
     * @return null|string
     */
    public function isAllowed(string $abstract)
    {
        return class_exists($abstract) ? $abstract : null;
    }


    /**
     * @param string $abstract
     *
     * @return bool
     */
    public function isShared(string $abstract) : bool
    {
        return $this->shared[ $abstract ] ?? false;
    }


    /**
     * @param string $id
     *
     * @return mixed
     *
     * @throws AutowireException
     * @throws NotFoundException
     */
    public function get(string $id)
    {
        if ($item = $this->isStored($id)) {
            return $item;
        }

        $result = $this->make($id);

        if (isset($this->shared[ $id ])) {
            $this->set($id, $result);
        }

        return $result;
    }

    /**
     * @param string $abstract
     *
     * @return string[]
     */
    public function getTags(string $abstract) : array
    {
        return $this->tags[ $abstract ] ?? [];
    }

    /**
     * @param string|ProviderInterface $provider
     *
     * @return null|ProviderInterface
     */
    public function getProvider($provider) : ProviderInterface
    {
        $provider = $this->hasProvider($provider);

        return $provider;
    }


    /**
     * @param string $id
     *
     * @return bool
     */
    public function has(string $id)
    {
        return ! empty(null
            ?? $this->isStored($id)
            ?? $this->isBound($id)
            ?? $this->isBlueprinted($id)
            ?? $this->isAllowed($id)
        );
    }

    /**
     * @param string|mixed $id
     *
     * @return null|string|callable
     */
    public function exists($id)
    {
        $abstract = is_string($id)
            ? ( null
                ?? $this->isBound($id)
                ?? $this->isBlueprinted($id)
            )
            : null;

        return $abstract;
    }


    /**
     * @param string|ProviderInterface $provider
     *
     * @return null|ProviderInterface
     */
    public function hasProvider($provider) : ?ProviderInterface
    {
        $providerClass = is_object($provider)
            ? get_class($provider)
            : $provider;

        if (! is_string($providerClass)) {
            return null;
        }

        $provider = $this->providers[ $providerClass ] ?? null;

        return $provider;
    }


    /**
     * @param string $abstract
     * @param mixed  $mixed
     *
     * @return static
     */
    public function set(string $abstract, $mixed)
    {
        $this->items[ $abstract ] = $mixed;

        return $this;
    }


    /**
     * @param string $abstract
     * @param string $concrete
     * @param array  $tags
     *
     * @return static
     *
     * @throws NotFoundException
     */
    public function bind(string $abstract, string $concrete, array $tags = [])
    {
        if (! $this->has($concrete)) {
            throw new NotFoundException(
                'Concrete not found: ' . $concrete
            );
        }

        $this->binds[ $abstract ] = $concrete;

        $newtags = null;
        foreach ( $this->tags[ $abstract ] ?? [] as $tag ) {
            $newtags[ $tag ] = true;
        }
        foreach ( $tags as $tag ) {
            $newtags[ $tag ] = true;
        }

        if (isset($newtags)) {
            $this->tags[ $abstract ] = array_keys($newtags);
        }

        return $this;
    }

    /**
     * @param string $abstract
     * @param string $concrete
     * @param array  $tags
     *
     * @return static
     * @throws NotFoundException
     */
    public function singleton(string $abstract, string $concrete, array $tags = [])
    {
        $this->bind($abstract, $concrete, $tags);

        $this->shared[ $abstract ] = true;

        return $this;
    }

    /**
     * @param string   $abstract
     * @param callable $callback
     * @param array    $tags
     *
     * @return static
     */
    public function factory(string $abstract, callable $callback, array $tags = [])
    {
        $this->factories[ $abstract ] = $callback;

        $newtags = [];
        foreach ( $this->tags[ $abstract ] ?? [] as $tag ) {
            $newtags[ $tag ] = true;
        }
        foreach ( $tags as $tag ) {
            $newtags[ $tag ] = true;
        }

        $this->tags[ $abstract ] = array_keys($newtags);

        return $this;
    }


    /**
     * @param string $abstract
     * @param string $propertyName
     * @param string $aware
     *
     * @return static
     *
     * @throws InvalidArgumentException
     */
    public function aware(string $abstract, string $propertyName, string $aware)
    {
        if (! is_a($abstract, AwareInterface::class, true)) {
            throw new InvalidArgumentException(
                'Abstract should implements ' . AwareInterface::class
            );
        }

        $this->aware[ $abstract ] = [ $propertyName, $aware ];

        return $this;
    }

    /**
     * @param string   $abstract
     * @param callable $callback
     *
     * @return static
     */
    public function extend(string $abstract, callable $callback)
    {
        $this->extends[ $abstract ][] = $callback;

        return $this;
    }

    /**
     * @param string   $tag
     * @param callable $callback
     *
     * @return static
     */
    public function extendTag(string $tag, callable $callback)
    {
        $this->extendTags[ $tag ][] = $callback;

        return $this;
    }


    /**
     * @param string|object|ProviderInterface $provider
     *
     * @return static
     * @throws FilesystemException
     */
    public function register($provider)
    {
        /** @var ProviderInterface $providerInstance */

        $instance = is_object($provider)
            ? $provider
            : new $provider($this);

        $providerInstance = null
            ?? ( $instance instanceof ProviderInterface ? $instance : null )
            ?? ( method_exists($instance, 'provides') ? new DeferableProviderDecorator($this, $instance) : null )
            ?? ( method_exists($instance, 'boot') ? new BootableProviderDecorator($this, $instance) : null )
            ?? new ProviderDecorator($this, $instance);

        $providerClass = get_class($instance);

        $this->providers[ $providerClass ] = $providerInstance;

        $providerInstance->register();

        if ($providerInstance instanceof DeferableProviderInterface) {
            $this->providersDeferable[ $providerClass ] = true;

            $deferables = $providerInstance->provides();

            foreach ( $deferables as $defer ) {
                $this->deferables[ $defer ][] = $providerClass;
            }

        } elseif ($providerInstance instanceof BootableProviderInterface) {
            $this->providersBootable[ $providerClass ] = true;

            if ($this->booted) {
                $this->bootProvider($providerInstance);
            }
        }

        if ($providerInstance instanceof DiscoverableProviderInterface) {
            $this->providersDiscovarable[ $providerClass ] = true;
            $this->providersDiscovered[ $providerClass ] = false;

            if ($this->discovered) {
                $this->discoverProvider($providerInstance);
            }
        }

        return $this;
    }


    /**
     * @param string $abstract
     * @param array  $params
     *
     * @return mixed
     *
     * @throws NotFoundException
     * @throws AutowireException
     */
    public function make(string $abstract, array $params = [])
    {
        $rootNode = new Node($this);

        $result = $rootNode->make($abstract, $params);

        // inject aware into properties
        if ($this->aware && $result instanceof AwareInterface) {
            foreach ( $this->aware as $abstract => [ $propertyName, $aware ] ) {
                if ($result instanceof $abstract) {
                    $result->{$propertyName} = $rootNode->get($aware);
                }
            }
        }

        // boot deferables
        if ($this->booted && isset($this->deferables[ $abstract ])) {
            foreach ( $this->deferables[ $abstract ] as $providerClass ) {
                /** @var DeferableProviderInterface $provider */

                $provider = $this->providers[ $providerClass ];

                $this->bootProvider($provider);
            }
        }

        // decorate result by name
        if (isset($this->extends[ $abstract ])) {
            foreach ( $this->extends[ $abstract ] as $callbacks ) {
                foreach ( $callbacks as $callback ) {
                    $result = $callback($result, $rootNode);
                }
            }
        }

        // decorate result by tags
        if (isset($this->tags[ $abstract ])) {
            foreach ( $this->tags[ $abstract ] as $tag ) {
                if (isset($this->extendTags[ $tag ])) {
                    foreach ( $this->extendTags[ $tag ] as $callbacks ) {
                        foreach ( $callbacks as $callback ) {
                            $result = $callback($result, $rootNode);
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param callable $callable
     * @param array    $params
     *
     * @return mixed
     *
     * @throws AutowireException
     * @throws NotFoundException
     */
    public function call(callable $callable, array $params = [])
    {
        $rootNode = new Node($this);

        $result = $rootNode->call($callable, $params);

        return $result;
    }


    /**
     * @return static
     *
     * @throws FilesystemException
     */
    public function discover()
    {
        if ($this->discovered) {
            return $this;
        }

        $this->discovered = true;

        foreach ( $this->providersDiscovarable as $providerClass => $bool ) {
            /** @var DiscoverableProviderInterface $provider */

            $provider = $this->providers[ $providerClass ];

            $this->discoverProvider($provider);
        }

        return $this;
    }


    /**
     * @param DiscoverableProviderInterface $provider
     *
     * @return static
     *
     * @throws FilesystemException
     */
    protected function discoverProvider(DiscoverableProviderInterface $provider)
    {
        if (! empty($this->providersDiscovered[ $providerClass = get_class($provider) ])) {
            return $this;
        }

        $sources = $provider->sources();
        $targets = $provider->targets();

        foreach ( $targets as $key => $target ) {
            if (isset($sources[ $key ])) {
                is_dir($sources[ $key ])
                    ? $this->copyDir($sources[ $key ], $target)
                    : $this->copyFile($sources[ $key ], $target);
            }
        }

        $this->providersDiscovered[ $providerClass ] = true;

        return $this;
    }


    /**
     * @param string $from
     * @param string $to
     *
     * @return static
     *
     * @throws FilesystemException
     */
    protected function copyDir(string $from, string $to)
    {
        /** @var \SplFileInfo $spl */

        if (! is_dir($from)) {
            throw new FilesystemException('Directory not found: ' . $from);
        }

        $it = new \RecursiveDirectoryIterator($from, \RecursiveDirectoryIterator::SKIP_DOTS);
        $iit = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::SELF_FIRST);

        foreach ( $iit as $spl ) {
            if (! $spl->isDir()) {
                $filepath = $to . '/' . $it->getSubPathname();
                $dirname = dirname($filepath);

                if (! is_dir($dirname)) {
                    mkdir($dirname, 0775, true);
                }

                if (! is_file($filepath)) {
                    copy($spl->getRealpath(), $filepath);
                }
            }
        }

        return $this;
    }

    /**
     * @param string $from
     * @param string $to
     *
     * @return static
     *
     * @throws FilesystemException
     */
    protected function copyFile(string $from, string $to)
    {
        if (! file_exists($from)) {
            throw new FilesystemException('File not found: ' . $from);
        }

        $dirname = dirname($to);
        if (! is_dir($dirname)) {
            mkdir($dirname, 0775, true);
        }

        if (! is_file($to)) {
            copy($from, $to);
        }

        return $this;
    }


    /**
     * @return static
     */
    public static function getInstance()
    {
        return static::$instances[ static::class ] = static::$instances[ static::class ]
            ?? new static();
    }

    /**
     * @param Di $di
     */
    public static function setInstance(Di $di)
    {
        static::$instances[ static::class ] = $di;
    }


    /**
     * @var static[]
     */
    protected static $instances = [];
}
