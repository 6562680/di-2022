<?php

namespace Gzhegow\Di;

use Gzhegow\Di\Domain\Node\NodeInterface;
use Gzhegow\Di\Domain\Provider\ProviderInterface;


/**
 * DiInterface
 */
interface DiInterface extends NodeInterface
{
    /**
     * @return static
     */
    public function boot();


    /**
     * @param string $abstract
     *
     * @return null|mixed
     */
    public function isStored(string $abstract);

    /**
     * @param string $abstract
     *
     * @return null|string
     */
    public function isBound(string $abstract);

    /**
     * @param string $abstract
     *
     * @return null|callable
     */
    public function isBlueprinted(string $abstract);

    /**
     * @param string $abstract
     *
     * @return null|string
     */
    public function isAllowed(string $abstract);


    /**
     * @param string $abstract
     *
     * @return bool
     */
    public function isShared(string $abstract) : bool;


    /**
     * @param string|mixed $id
     *
     * @return null|string|callable
     */
    public function exists($id);


    /**
     * @param string $abstract
     * @param mixed  $mixed
     *
     * @return static
     */
    public function set(string $abstract, $mixed);


    /**
     * @param string $abstract
     * @param string $concrete
     * @param array  $tags
     *
     * @return static
     */
    public function bind(string $abstract, string $concrete, array $tags = []);

    /**
     * @param string $abstract
     * @param string $concrete
     * @param array  $tags
     *
     * @return static
     */
    public function singleton(string $abstract, string $concrete, array $tags = []);

    /**
     * @param string   $abstract
     * @param callable $callback
     * @param array    $tags
     *
     * @return static
     */
    public function factory(string $abstract, callable $callback, array $tags = []);


    /**
     * @param string $abstract
     * @param string $propertyName
     * @param string $aware
     *
     * @return static
     */
    public function aware(string $abstract, string $propertyName, string $aware);

    /**
     * @param string   $abstract
     * @param callable $callback
     *
     * @return static
     */
    public function extend(string $abstract, callable $callback);

    /**
     * @param string   $tag
     * @param callable $callback
     *
     * @return static
     */
    public function extendTag(string $tag, callable $callback);


    /**
     * @param string|object|ProviderInterface $provider
     *
     * @return static
     */
    public function register($provider);


    /**
     * @param string $abstract
     *
     * @return string[]
     */
    public function getTags(string $abstract) : array;


    /**
     * @return static
     */
    public function discover();
}
