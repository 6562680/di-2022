<?php


namespace Gzhegow\Di;


/**
 * Node
 */
class Node implements NodeInterface
{
    /**
     * @var Di
     */
    protected $di;

    /**
     * @var Node
     */
    protected $parent;


    /**
     * @var string
     */
    protected $abstract;


    /**
     * Constructor
     *
     * @param Di   $di
     * @param null $parent
     */
    public function __construct(Di $di, $parent = null)
    {
        $this->di = $di;

        $this->parent = $parent;
    }


    /**
     * @param string $id
     *
     * @return mixed
     */
    public function get(string $id)
    {
        $result = ( new Node($this->di, $this) )
            ->make($id);

        return $result;
    }


    /**
     * @param string $id
     *
     * @return bool
     */
    public function has(string $id)
    {
        return $this->di->has($id);
    }


    /**
     * @param string $abstract
     * @param array  $params
     *
     * @return mixed
     */
    public function make(string $abstract, array $params = [])
    {
        $list[] = $this->abstract = $abstract;

        $current = $this;
        while ( $current = $current->parent ) {
            $list[] = $current->abstract;

            if ($current->abstract === $abstract) {
                throw new \RuntimeException(
                    'Recursion: ' . implode(' -> ', array_reverse($list))
                );
            }
        }

        $current = $abstract;
        while ( $bound = $this->di->exists($current) ) {
            $current = $bound;
        }

        $result = is_string($current)
            ? new $current(...$this->autowireConstructor($current, $params))
            : $this->call($current, [ 0 => $this ] + $params);

        return $result;
    }

    /**
     * @param callable $callable
     * @param array    $params
     *
     * @return mixed
     */
    public function call(callable $callable, array $params = [])
    {
        $list[] = $this->abstract = $callable;

        $current = $this;
        while ( $current = $current->parent ) {
            $list[] = $current->abstract;

            if ($current->abstract === $callable) {
                throw new \RuntimeException(
                    'Recursion: ' . implode(' -> ', array_reverse($list))
                );
            }
        }

        try {
            $reflectionFunction = null
                ?? ( is_array($callable) && is_object($callable[ 0 ])
                    ? new \ReflectionMethod($callable[ 0 ], $callable[ 1 ])
                    : null
                )
                ?? new \ReflectionFunction($callable);
        }
        catch ( \ReflectionException $e ) {
            throw new \RuntimeException($e->getMessage(), null, $e);
        }

        $result = call_user_func_array($callable, $this->autowireCallable($reflectionFunction, $params));

        return $result;
    }


    /**
     * @param string $className
     * @param array  $params
     *
     * @return array
     */
    protected function autowireConstructor(string $className, array $params = []) : array
    {
        try {
            $rc = new \ReflectionClass($className);
        }
        catch ( \ReflectionException $e ) {
            throw new \RuntimeException($e->getMessage(), null, $e);
        }

        $reflectionFunction = $rc->getConstructor();

        if ($reflectionFunction) {
            $paramsAutowired = $this->autowireCallable($reflectionFunction, $params);

        } else {
            $paramsInt = [];

            foreach ( $params as $i => $param ) {
                if (is_int($i)) {
                    $paramsInt[ $i ] = $param;
                }
            }

            $paramsAutowired = $paramsInt;
        }

        return $paramsAutowired;
    }

    /**
     * @param \ReflectionFunctionAbstract $reflectionFunction
     * @param array                       $params
     *
     * @return array
     */
    protected function autowireCallable(\ReflectionFunctionAbstract $reflectionFunction, array $params = []) : array
    {
        $paramsAutowired = [];

        $paramsInt = [];
        $paramsString = [];
        foreach ( $params as $i => $param ) {
            is_int($i)
                ? ( $paramsInt[ $i ] = $param )
                : ( $paramsString[ $i ] = $param );
        }

        foreach ( $reflectionFunction->getParameters() as $i => $rp ) {
            $rpName = $rp->getName();

            $rpTypeName = null;
            $rpType = $rp->getType();
            if (is_a($rpType, 'ReflectionNamedType')) {
                if (class_exists($rpType->getName()) || interface_exists($rpType->getName())) {
                    $rpTypeName = $rpType->getName();
                }
            }

            if ($rpTypeName && isset($paramsString[ $rpTypeName ])) {
                $value = $paramsString[ $rpTypeName ];

                $paramsAutowired[ $i ] = $value;
                array_unshift($paramsInt, $value);

            } elseif (isset($paramsString[ '$' . $rpName ])) {
                $value = $paramsString[ '$' . $rpName ];

                $paramsAutowired[ $i ] = $value;
                array_unshift($paramsInt, $value);

            } elseif (isset($params[ $i ])) {
                $paramsAutowired[ $i ] = $params[ $i ];

            } elseif ($rpTypeName && $this->has($rpTypeName)) {
                $instance = $this->get($rpTypeName);

                $paramsAutowired[ $i ] = $instance;
                $paramsString[ $rpName ] = $instance;
                array_unshift($paramsInt, $instance);

            } else {
                $paramsAutowired[ $i ] = null;
            }
        }

        return $paramsAutowired;
    }
}
