<?php

namespace Gzhegow\Di;

use Psr\Container\ContainerInterface;
use Gzhegow\Di\Exceptions\RuntimeException;
use Gzhegow\Di\Exceptions\Exception\NotFoundException;
use Gzhegow\Di\Exceptions\Runtime\AutowireException;
use Gzhegow\Di\Exceptions\Logic\InvalidArgumentException;

/**
 * Class Loop
 */
class Loop
{
	/**
	 * @var Di
	 */
	protected $di;

	/**
	 * @var array
	 */
	protected $loop = [];


	/**
	 * Constructor
	 *
	 * @param Di $di
	 */
	public function __construct(Di $di)
	{
		$this->di = $di;
	}


	/**
	 * @return array
	 */
	public function getLoop() : array
	{
		return array_keys($this->loop);
	}

	/**
	 * @return array
	 */
	public function getLoopIndex() : array
	{
		return $this->loop;
	}


	/**
	 * @param string $id
	 *
	 * @return null|mixed
	 * @throws NotFoundException
	 */
	public function get(string $id)
	{
		if (! is_string($id)) {
			throw new InvalidArgumentException('Id should be string');
		}

		if ('' === $id) {
			throw new InvalidArgumentException('Id should be not empty');
		}

		$result = null;

		if ($this->di->hasItem($id)) {
			$result = $this->di->getItem($id);

		} else {
			if (! $this->di->hasBind($id)) {
				$bind = $id;

			} else {
				$bind = $this->di->getBind($id);

				if ($this->di->hasItem($bind)) {
					$result = $this->di->getItem($bind);

				} elseif ($this->isClosure($bind)) {
					$result = $this->handle($bind);

				}
			}

			if (! $result) {
				$result = $this->createAutowired($bind);
			}
		}

		return $result;
	}


	/**
	 * @param string $id
	 *
	 * @return null|mixed
	 */
	protected function getAsChild(string $id)
	{
		try {
			$instance = $this->di->newLoop($this->loop)->get($id);
		}
		catch ( NotFoundException $exception ) {
			throw new RuntimeException(null, null, $exception);
		}

		return $instance;
	}


	/**
	 * @param mixed $func
	 *
	 * @return bool
	 */
	protected function isCallable($func) : bool
	{
		return ( is_array($func) || is_string($func) )
			&& is_callable($func);
	}

	/**
	 * @param mixed $func
	 *
	 * @return bool
	 */
	protected function isCallableArray($func) : bool
	{
		return is_array($func) && is_callable($func);
	}

	/**
	 * @param mixed $func
	 *
	 * @return bool
	 */
	protected function isCallableString($func) : bool
	{
		return is_string($func) && is_callable($func);
	}

	/**
	 * @param mixed $class
	 *
	 * @return bool
	 */
	protected function isClass($class) : bool
	{
		return is_string($class) && class_exists($class);
	}

	/**
	 * @param mixed $func
	 *
	 * @return bool
	 */
	protected function isClosure($func) : bool
	{
		return is_object($func) && ( get_class($func) === \Closure::class );
	}

	/**
	 * @param mixed $handler
	 *
	 * @return bool
	 */
	protected function isHandler($handler) : bool
	{
		return is_string($handler)
			&& ( '' !== $handler )
			&& ( $handler[ 0 ] !== '@' )
			&& ( false !== strpos($handler, '@') );
	}

	/**
	 * @param mixed $reflectionClass
	 *
	 * @return bool
	 */
	protected function isReflectionClass($reflectionClass) : bool
	{
		return is_object($reflectionClass) && is_a($reflectionClass, \ReflectionClass::class);
	}


	/**
	 * @param string $id
	 * @param array  $params
	 *
	 * @return null|mixed
	 * @throws NotFoundException
	 */
	public function createAutowired(string $id, array $params = [])
	{
		if ('' === $id) {
			throw new InvalidArgumentException('Id should be not empty');
		}

		if (! ( 0
			|| ( $hasBind = $this->di->hasBind($id) )
			|| ( $isCLass = $this->isClass($id) )
		)) {
			throw new NotFoundException('Bind not found: ' . $id);
		}

		$bind = null
			?? ( $hasBind
				? $this->di->getBind($id)
				: null )
			?? ( $isCLass
				? $id
				: null );

		if (isset($this->loop[ $bind ])) {
			throw new AutowireException(sprintf(
				'Autowire loop: %s is required in [ %s ]',
				$bind,
				implode(' <- ', array_keys($this->loop))
			));
		}

		$this->loop[ $bind ] = true;

		if ($this->di->hasDeferableBind($bind)) {
			$this->di->bootDeferable($bind);
		}

		switch ( true ):
			case ( $this->isClosure($bind) ):
				$item = $this->handle($bind, $params);

				break;

			case ( $this->isClass($bind) ):
				$arguments = $this->autowireClass($bind, $params);

				ksort($arguments);

				$item = new $bind(...$arguments);

				break;

			default:
				throw new RuntimeException('Unsupported bind type: ' . gettype($bind));

		endswitch;

		if ($this->di->hasExtends($id)) {
			foreach ( $this->di->getExtends($id) as $func ) {
				$item = null
					?? $this->handle($func, [
						$id => $item,
					])
					?? $item;
			}
		}

		if ($this->di->hasShared($id)) {
			if (! $this->di->hasItem($id)) {
				$this->di->set($id, $item);
			}
		}

		if ($this->di->hasShared($bind)) {
			if (! $this->di->hasItem($bind)) {
				$this->di->set($bind, $item);
			}
		}

		return $item;
	}


	/**
	 * @param object $newthis
	 * @param mixed  $func
	 * @param mixed  ...$arguments
	 *
	 * @return mixed
	 */
	public function call($newthis, $func, ...$arguments)
	{
		if (! is_object($newthis)) {
			throw new InvalidArgumentException('NewThis should be object');
		}

		$result = $this->apply($newthis, $func, $arguments);

		return $result;
	}


	/**
	 * @param mixed $newthis
	 * @param mixed $func
	 * @param array $params
	 *
	 * @return mixed
	 */
	public function apply($newthis, $func, array $params = [])
	{
		/** @var \Closure $closure */

		if (! is_object($newthis)) {
			throw new InvalidArgumentException('NewThis should be object');
		}

		if (! ( 0
			|| ( $isClosure = $this->isClosure($func) )
			|| ( $isCallable = $this->isCallable($func) )
		)) {
			throw new InvalidArgumentException('Func should be closure, handler or callable');
		}

		$params += [ get_class($newthis) => $newthis ];

		$arguments = [];
		switch ( true ) {
			case $isClosure:
				$this->loop[ \Closure::class ] = true;

				$arguments = $this->autowireClosure($func, $params);
				break;

			case $isCallable:
				$this->loop[ 'callable' ] = true;

				$arguments = $this->autowireCallable($func, $params);
				break;
		}

		switch ( true ) {
			case $isClosure:
				$closure = $func;
				break;

			case $isCallable:
				$closure = \Closure::fromCallable($func);
				break;
		}

		ksort($arguments);

		$result = $closure->call($newthis, ...$arguments);

		return $result;
	}

	/**
	 * @param callable $func
	 * @param array    $params
	 *
	 * @return mixed
	 */
	public function handle($func, array $params = [])
	{
		/** @var \Closure $closure */

		if (! ( 0
			|| ( $isHandler = $this->isHandler($func) )
			|| ( $isClosure = $this->isClosure($func) )
			|| ( $isCallable = $this->isCallableArray($func) )
		)) {
			throw new InvalidArgumentException('Func should be closure, handler or callable');
		}

		$arguments = [];
		switch ( true ) {
			case $isHandler:
				[ $id, $method ] = explode('@', $func) + [ null, null ];

				$func = [ $object = $this->getAsChild($id), $method ];

				$arguments = $this->autowireMethod($object, $method, $params);

				break;

			case $isClosure:
				$arguments = $this->autowireClosure($func, $params);
				break;

			case $isCallable:
				$arguments = $this->autowireCallable($func, $params);
				break;
		}

		ksort($arguments);

		$result = call_user_func_array($func, $arguments);

		return $result;
	}


	/**
	 * @param string $class
	 * @param array  $params
	 *
	 * @return array
	 */
	protected function autowireClass(string $class, array $params = []) : array
	{
		$rm = $this->reflectClass($class)->getConstructor();

		$result = isset($rm)
			? $this->autowireParams($rm->getParameters(), $params)
			: [];

		return $result;
	}


	/**
	 * @param mixed  $class
	 * @param string $method
	 * @param array  $params
	 *
	 * @return array
	 */
	protected function autowireMethod($class, string $method, array $params = []) : array
	{
		if (! ( 0
			|| is_string($class)
			|| is_object($class)
		)) {
			throw new InvalidArgumentException('Object should be typeof object');
		}

		$result = null
			?? $this->autowireMethodClass($class, $method, $params)
			?? $this->autowireMethodObject($object = $class, $method, $params);

		return $result;
	}

	/**
	 * @param mixed  $class
	 * @param string $method
	 * @param array  $params
	 *
	 * @return array
	 */
	protected function autowireMethodClass($class, string $method, array $params = []) : ?array
	{
		if (! is_string($class)) return null;

		$rm = $this->reflectMethod($this->reflectClass($class), $method);

		$result = $this->autowireParams($rm->getParameters(), $params);

		return $result;
	}

	/**
	 * @param mixed  $class
	 * @param string $method
	 * @param array  $params
	 *
	 * @return array
	 */
	protected function autowireMethodObject($class, string $method, array $params = []) : array
	{
		if (! is_object($class)) return null;

		$rm = $this->reflectMethod($this->reflectClass($class), $method);

		$result = $this->autowireParams($rm->getParameters(), $params);

		return $result;
	}


	/**
	 * @param mixed $callable
	 * @param array $params
	 *
	 * @return array
	 */
	protected function autowireCallable($callable, array $params = []) : array
	{
		$result = null
			?? $this->autowireCallableArray($callable, $params)
			?? $this->autowireCallableClosure($callable, $params)
			?? $this->autowireCallableString($callable, $params);

		return $result;
	}


	/**
	 * @param mixed $callable
	 * @param array $params
	 *
	 * @return array
	 */
	protected function autowireCallableArray($callable, array $params = []) : ?array
	{
		if (! is_array($callable)) return null;

		if (! $this->isCallable($callable)) {
			throw new InvalidArgumentException('Callable should be callable');
		}

		$rm = $this->reflectMethod($callable[ 0 ], $callable[ 1 ]);

		$result = null
			?? $this->autowireCallableMethodPublic($rm, $callable[ 0 ], $params)
			?? $this->autowireCallableMethodStatic($rm, $params);

		return $result;
	}

	/**
	 * @param \ReflectionMethod $rm
	 * @param mixed             $object
	 * @param array             $params
	 *
	 * @return array
	 */
	protected function autowireCallableMethodPublic(\ReflectionMethod $rm, $object, array $params = []) : ?array
	{
		if ($rm->isStatic()) return null;

		if (! is_object($object)) {
			throw new InvalidArgumentException('Object should be instance');
		}

		$result = $this->autowireParams($rm->getParameters(), $params);

		return $result;
	}

	/**
	 * @param \ReflectionMethod $rm
	 * @param array             $params
	 *
	 * @return array
	 */
	protected function autowireCallableMethodStatic(\ReflectionMethod $rm, array $params = []) : ?array
	{
		if (! $rm->isStatic()) return null;

		$result = $this->autowireParams($rm->getParameters(), $params);

		return $result;
	}

	/**
	 * @param mixed $closure
	 * @param array $params
	 *
	 * @return array
	 */
	protected function autowireCallableClosure($closure, array $params = []) : ?array
	{
		if (! is_object($closure)) return null;

		if (! $this->isClosure($closure)) {
			throw new InvalidArgumentException('Closure should be correct closure');
		}

		$result = $this->autowireClosure($closure, $params);

		return $result;
	}

	/**
	 * @param mixed $callable
	 * @param array $params
	 *
	 * @return array
	 */
	protected function autowireCallableString($callable, array $params = []) : ?array
	{
		if (! is_string($callable)) return null;

		if (! $this->isCallableString($callable)) {
			throw new InvalidArgumentException('Callable should be correct callable');
		}

		$rf = $this->reflectCallable($callable);

		$result = $this->autowireParams($rf->getParameters(), $params);

		return $result;
	}


	/**
	 * @param mixed $closure
	 * @param array $params
	 *
	 * @return array
	 */
	protected function autowireClosure($closure, array $params = []) : ?array
	{
		if (! $this->isClosure($closure)) {
			throw new InvalidArgumentException('Closure should be correct closure');
		}

		$rf = $this->reflectClosure($closure);

		$result = $this->autowireParams($rf->getParameters(), $params);

		return $result;
	}


	/**
	 * @param \ReflectionParameter[] $reflectionParameters
	 * @param array                  $params
	 *
	 * @return array
	 */
	protected function autowireParams(array $reflectionParameters, array $params = []) : array
	{
		$used = [];

		$append = [
			ContainerInterface::class,
			DiInterface::class,
			Di::class,

			'$di',
			'$container',
		];
		foreach ( $append as $key ) {
			$params += [ $key => $this->di ];
		}

		$int = [];
		$str = [];
		foreach ( $params as $key => $val ) {
			if (is_int($key)) {
				$int[ $key ] = $val;
			} else {
				$str[ $key ] = $val;
			}
		}

		$args = [];

		if ($reflectionParameters) {
			foreach ( $reflectionParameters as $rp ) {
				$result = $this->autowireParam($rp, $int, $str, $used);

				if (count($result)) {
					$args[ $rp->getPosition() ] = reset($result);
					continue;
				}

				if ($rp->isVariadic()) {
					continue;
				}

				throw new AutowireException(
					sprintf('Unable to autowire parameter %d (%s)', $rp->getPosition(), $rp->getName())
				);
			}
		}

		$keys = array_keys($args);
		sort($keys);

		$result = [];
		$idx = 0;
		foreach ( $keys as $key ) {
			if (! is_int($key)) {
				$key = $idx;
			}

			$result[ $key ] = $args[ $key ];
			$idx++;
		}

		return $result;
	}

	/**
	 * @param \ReflectionParameter $rp
	 * @param array                $int
	 * @param array                $str
	 * @param array                $used
	 *
	 * @return array
	 */
	protected function autowireParam(\ReflectionParameter $rp, array &$int = [], array &$str = [], array &$used = []) : array
	{
		$order = [
			[ $this, 'autowireParamType' ],
			[ $this, 'autowireParamName' ],
			[ $this, 'autowireParamPosition' ],
			[ $this, 'autowireParamDefault' ],
		];

		$autowireResult = [];

		foreach ( $order as $func ) {
			$autowireResult = $func($rp, $int, $str, $used);

			if (count($autowireResult)) break;
		}

		return $autowireResult;
	}

	/**
	 * @param \ReflectionParameter $rp
	 * @param array                $int
	 * @param array                $str
	 * @param array                $used
	 *
	 * @return array
	 */
	protected function autowireParamType(\ReflectionParameter $rp, array &$int = [], array &$str = [], array &$used = []) : array
	{
		if (! $rpType = $rp->getType()) return [];
		if (! $rpTypeName = $rpType->getName()) return [];

		if (array_key_exists($rpTypeName, $str)
			&& is_object($str[ $rpTypeName ])
			&& is_a($str[ $rpTypeName ], $rpTypeName)
		) {
			$value = $str[ $rpTypeName ];

			$used[ $rpTypeName ] = true;

			$int = $this->array_expand($int, $rp->getPosition(), $value);

			return [ $value ];

		} elseif (interface_exists($rpTypeName) || class_exists($rpTypeName)) {
			$value = $this->getAsChild($rpTypeName);

			$int = $this->array_expand($int, $rp->getPosition(), $value);

			return [ $value ];
		}

		return [];
	}

	/**
	 * @param \ReflectionParameter $rp
	 * @param array                $int
	 * @param array                $str
	 * @param array                $used
	 *
	 * @return array
	 */
	protected function autowireParamName(\ReflectionParameter $rp, array &$int = [], array &$str = [], array &$used = [])
	{
		if (! $rpName = $rp->getName()) return [];
		if (! array_key_exists($key = '$' . $rpName, $str)) return [];

		$used[ $key ] = true;

		if ($rp->isVariadic()) {
			if (is_null($str[ $key ]) || ( [] === $str[ $key ] )) {
				return [];
			}
		}

		$value = $str[ $key ];

		$int = $this->array_expand($int, $rp->getPosition(), $value);

		return [ $value ];
	}

	/**
	 * @param \ReflectionParameter $rp
	 * @param array                $int
	 * @param array                $str
	 * @param array                $used
	 *
	 * @return array
	 */
	protected function autowireParamPosition(\ReflectionParameter $rp, array &$int = [], array &$str = [], array &$used = []) : array
	{
		if (! array_key_exists($rpPos = $rp->getPosition(), $int)) return [];

		if ($rp->isVariadic()) {
			if (is_null($int[ $rpPos ]) || ( [] === $int[ $rpPos ] )) {
				return [];
			}
		}

		$value = $int[ $rpPos ];

		return [ $value ];
	}

	/**
	 * @param \ReflectionParameter $rp
	 * @param array                $int
	 * @param array                $str
	 * @param array                $used
	 *
	 * @return array
	 */
	protected function autowireParamDefault(\ReflectionParameter $rp, array &$int = [], array &$str = [], array &$used = []) : array
	{
		try {
			$value = $rp->getDefaultValue();
		}
		catch ( \ReflectionException $exception ) {
			return [];
		}

		return [ $value ];
	}


	/**
	 * @param mixed $object
	 *
	 * @return \ReflectionClass
	 */
	protected function reflectClass($object) : \ReflectionClass
	{
		try {
			if (is_object($object)) {
				if (is_a($object, \ReflectionClass::class)) {
					$rc = $object;

				} else {
					$rc = new \ReflectionClass(get_class($object));

				}
			} else {
				$rc = new \ReflectionClass($object);

			}
		}
		catch ( \ReflectionException $e ) {
			throw new RuntimeException(null, null, $e);
		}

		return $rc;
	}

	/**
	 * @param mixed  $object
	 * @param string $method
	 *
	 * @return \ReflectionMethod
	 */
	protected function reflectMethod($object, string $method) : \ReflectionMethod
	{
		/** @var \ReflectionClass $reflectionClass */

		try {
			if ($this->isReflectionClass($reflectionClass = $object)) {
				$rm = $reflectionClass->getMethod($method);

			} else {
				$rm = new \ReflectionMethod($object, $method);

			}
		}
		catch ( \ReflectionException $e ) {
			throw new RuntimeException(null, null, $e);
		}

		return $rm;
	}


	/**
	 * @param \Closure $func
	 *
	 * @return \ReflectionFunction
	 */
	protected function reflectClosure(\Closure $func) : \ReflectionFunction
	{
		try {
			$rf = new \ReflectionFunction($func);
		}
		catch ( \ReflectionException $e ) {
			throw new RuntimeException(null, null, $e);
		}

		return $rf;
	}

	/**
	 * @param callable $callable
	 *
	 * @return \ReflectionFunction|\ReflectionMethod
	 */
	protected function reflectCallable($callable)
	{
		try {
			if ($this->isClosure($callable)) {
				$rf = $this->reflectClosure($callable);

			} elseif (is_array($callable)) {
				$rf = $this->reflectMethod($callable[ 0 ], $callable[ 1 ]);

			} else {
				$rf = new \ReflectionFunction($callable);

			}
		}
		catch ( \ReflectionException $e ) {
			throw new RuntimeException(null, null, $e);
		}

		return $rf;
	}


	/**
	 * @param array $array
	 * @param int   $pos
	 * @param null  $value
	 *
	 * @return array
	 */
	protected function array_expand(array $array, int $pos, $value = null) : array
	{
		if ($pos < 0) {
			throw new InvalidArgumentException('Pos should be non-negative');
		}

		$result = array_merge(
			array_slice($array, 0, $pos),
			[ $pos => $value ],
			array_slice($array, $pos)
		);

		return $result;
	}
}