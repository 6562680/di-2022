<?php


namespace Gzhegow\Di\Domain\Node;

use Gzhegow\Di\Di;
use Gzhegow\Di\Exceptions\Runtime\NotFoundException;
use Gzhegow\Di\Exceptions\Runtime\AutowireException;


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
     *
     * @throws AutowireException
     * @throws NotFoundException
     */
    public function get(string $id)
    {
        $node = new Node($this->di, $this);

        $result = $node->make($id);

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
     * @param array  $parameters
     *
     * @return mixed
     *
     * @throws AutowireException
     * @throws NotFoundException
     */
    public function make(string $abstract, array $parameters = [])
    {
        if (! $this->has($abstract)) {
            throw new NotFoundException(
                'Unable to make: ' . $abstract
            );
        }

        $list[] = $this->abstract = $abstract;

        $current = $this;
        while ( $current = $current->parent ) {
            $list[] = $current->abstract;

            if ($current->abstract === $abstract) {
                throw new AutowireException(
                    'Autowire recursion: ' . implode(' -> ', array_reverse($list))
                );
            }
        }

        $current = $abstract;
        while ( $bound = $this->di->exists($current) ) {
            $current = $bound;
        }

        if (is_string($current) && class_exists($current)) {
            $parameters = $this->autowireConstructor($current, $parameters);

            try {
                $result = new $current(...$parameters);
            }
            catch ( \Throwable $e ) {
                throw new AutowireException('Unable to make: ' . $abstract, null, $e);
            }

        } elseif (is_callable($current)) {
            $result = $this->call($current, [ 0 => $this ] + $parameters);

        } else {
            throw new NotFoundException(
                'Unable to make: ' . $abstract
            );
        }

        return $result;
    }

    /**
     * @param callable $callable
     * @param array    $parameters
     *
     * @return mixed
     *
     * @throws AutowireException
     * @throws NotFoundException
     */
    public function call(callable $callable, array $parameters = [])
    {
        $list[] = $this->abstract = $callable;

        $current = $this;
        while ( $current = $current->parent ) {
            $list[] = $current->abstract;

            if ($current->abstract === $callable) {
                throw new AutowireException(
                    'Autowire recursion: ' . implode(' -> ', array_reverse($list))
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
            throw new AutowireException($e->getMessage(), null, $e);
        }

        $result = call_user_func_array($callable, $this->autowireCallable($reflectionFunction, $parameters));

        return $result;
    }


    /**
     * @param string $className
     * @param array  $parameters
     *
     * @return array
     *
     * @throws AutowireException
     * @throws NotFoundException
     */
    protected function autowireConstructor(string $className, array $parameters = []) : array
    {
        try {
            $rc = new \ReflectionClass($className);
        }
        catch ( \ReflectionException $e ) {
            throw new AutowireException($e->getMessage(), null, $e);
        }

        $reflectionFunction = $rc->getConstructor();

        if ($reflectionFunction) {
            $paramsAutowired = $this->autowireCallable($reflectionFunction, $parameters);

        } else {
            $paramsInt = [];

            foreach ( $parameters as $i => $param ) {
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
     * @param array                       $parameters
     *
     * @return array
     *
     * @throws AutowireException
     * @throws NotFoundException
     */
    protected function autowireCallable(\ReflectionFunctionAbstract $reflectionFunction, array $parameters = []) : array
    {
        $parameters = $parameters ?? [];

        $paramsAutowired = [];

        $paramsInt = [];
        $paramsString = [];
        foreach ( $parameters as $i => $param ) {
            if (is_int($i)) {
                $paramsInt[ $i ] = $param;

            } elseif (is_string($i) && strlen($i)) {
                if (class_exists($i) || interface_exists($i)) {
                    $paramsString[ $i ] = $param;

                } else {
                    $paramsString[ '$' . ltrim($i, '$') ] = $param;
                }
            }
        }

        foreach ( $reflectionFunction->getParameters() as $i => $rp ) {
            $rpName = $rp->getName();

            $rpTypeName = null;
            $rpType = $rp->getType();
            if ($rpType && ! $this->reflectionTypeIsBuiltin($rpType)) {
                if ($this->reflectionTypeIsNamed($rpType)
                    && ( 0
                        || class_exists($rpType->getName())
                        || interface_exists($rpType->getName())
                    )
                ) {
                    $rpTypeName = $rpType->getName();
                }
            }

            if (isset($paramsString[ $paramKey = '$' . $rpName ])) {
                $value = $paramsString[ $paramKey ];

                $paramsAutowired[ $i ] = $value;
                array_unshift($paramsInt, null);

            } elseif ($rpTypeName) {
                $instance = null;

                if (isset($paramsString[ $rpTypeName ])) {
                    $instance = $paramsString[ $rpTypeName ];

                } elseif (isset($paramsInt[ $i ])
                    && $paramsInt[ $i ] instanceof $rpTypeName
                ) {
                    $instance = $paramsInt[ $i ];
                    $paramsInt[ $i ] = null;

                } elseif ($this->has($rpTypeName)) {
                    $instance = $this->get($rpTypeName);
                }

                $paramsAutowired[ $i ] = $instance;
                array_unshift($paramsInt, null);

            } elseif (isset($paramsInt[ $i ])) {
                $paramsAutowired[ $i ] = $paramsInt[ $i ];
                $paramsInt[ $i ] = null;

            } elseif (! $rp->isVariadic()) {
                $paramsAutowired[ $i ] = null;
            }
        }

        $paramsAutowired += array_filter($paramsInt);

        return $paramsAutowired;
    }


    /**
     * @param \ReflectionType $reflectionType
     *
     * @return bool
     */
    protected function reflectionTypeIsNamed(\ReflectionType $reflectionType) : bool
    {
        return is_a($reflectionType, 'ReflectionNamedType');
    }

    /**
     * @param \ReflectionType $reflectionType
     *
     * @return bool
     */
    protected function reflectionTypeIsBuiltin(\ReflectionType $reflectionType) : bool
    {
        $isBuiltIn = false;

        try {
            $isBuiltIn = $reflectionType->{'isBuiltin'}();
        }
        catch ( \Throwable $e ) {
        }

        return $isBuiltIn;
    }
}
