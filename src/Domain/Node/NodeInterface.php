<?php

namespace Gzhegow\Di\Domain\Node;

use Psr\Container\ContainerInterface;


/**
 * NodeInterface
 */
interface NodeInterface extends ContainerInterface
{
    /**
     * @param string $abstract
     * @param array  $parameters
     *
     * @return mixed
     */
    public function make(string $abstract, array $parameters = []);

    /**
     * @param callable $callable
     * @param array    $parameters
     *
     * @return mixed
     */
    public function call(callable $callable, array $parameters = []);
}
