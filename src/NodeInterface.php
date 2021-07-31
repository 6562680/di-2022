<?php

namespace Gzhegow\Di;

use Psr\Container\ContainerInterface;


/**
 * NodeInterface
 */
interface NodeInterface extends ContainerInterface
{
    /**
     * @param string $abstract
     * @param array  $params
     *
     * @return mixed
     */
    public function make(string $abstract, array $params = []);
}
