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
        $this->abstract = $abstract;
        $list[] = $abstract;

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

        $result = null
            ?? ( is_callable($current) ? $current($this, $params) : null )
            ?? new $current(...$this->autowireConstructor($current, $params));

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

        $paramsInt = [];
        $paramsString = [];
        foreach ( $params as $i => $param ) {
            if (is_int($i)) {
                $paramsInt[ $i ] = $param;
            } else {
                $paramsString[ $i ] = $param;
            }
        }

        if (! $rm = $rc->getConstructor()) {
            $paramsAutowired = $paramsInt;

        } else {
            $paramsAutowired = [];

            foreach ( $rm->getParameters() as $i => $rp ) {
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
        }

        return $paramsAutowired;
    }
}
