<?php

namespace Gzhegow\Di;

use Gzhegow\Di\Domain\Registry\BindRegistry;
use Gzhegow\Di\Domain\Registry\ItemRegistry;
use Gzhegow\Di\Domain\Registry\SharedRegistry;
use Gzhegow\Di\Domain\Registry\ExtendRegistry;
use Gzhegow\Di\Domain\Node\NodeFactoryInterface;
use Gzhegow\Di\Exceptions\Runtime\OverflowException;

/**
 * DiManager
 */
class DiManager
{
    /**
     * @var NodeFactoryInterface
     */
    protected $nodeFactory;

    /**
     * @var BindRegistry
     */
    protected $bindRegistry;
    /**
     * @var SharedRegistry
     */
    protected $sharedRegistry;
    /**
     * @var ItemRegistry
     */
    protected $itemRegistry;
    /**
     * @var ExtendRegistry
     */
    protected $extendRegistry;


    /**
     * Constructor
     *
     * @param NodeFactoryInterface $nodeFactory
     *
     * @param BindRegistry         $bindRegistry
     * @param ExtendRegistry       $extendRegistry
     * @param ItemRegistry         $itemRegistry
     * @param SharedRegistry       $sharedRegistry
     *
     */
    public function __construct(
        NodeFactoryInterface $nodeFactory,

        BindRegistry $bindRegistry,
        ExtendRegistry $extendRegistry,
        ItemRegistry $itemRegistry,
        SharedRegistry $sharedRegistry
    )
    {
        $this->nodeFactory = $nodeFactory;

        $this->bindRegistry = $bindRegistry;
        $this->extendRegistry = $extendRegistry;
        $this->itemRegistry = $itemRegistry;
        $this->sharedRegistry = $sharedRegistry;
    }


    /**
     * @return BindRegistry
     */
    public function getBindRegistry() : BindRegistry
    {
        return $this->bindRegistry;
    }

    /**
     * @return ExtendRegistry
     */
    public function getExtendRegistry() : ExtendRegistry
    {
        return $this->extendRegistry;
    }

    /**
     * @return ItemRegistry
     */
    public function getItemRegistry() : ItemRegistry
    {
        return $this->itemRegistry;
    }

    /**
     * @return SharedRegistry
     */
    public function getSharedRegistry() : SharedRegistry
    {
        return $this->sharedRegistry;
    }


    /**
     * @param mixed $id
     *
     * @return bool
     */
    public function hasItem($id) : bool
    {
        return $this->itemRegistry->hasItem($id);
    }

    /**
     * @param mixed $id
     *
     * @return bool
     */
    public function hasBind($id) : bool
    {
        return $this->bindRegistry->hasBind($id);
    }

    /**
     * @param mixed $id
     *
     * @return bool
     */
    public function hasExtend($id) : bool
    {
        return $this->extendRegistry->hasExtend($id);
    }

    /**
     * @param mixed $id
     *
     * @return bool
     */
    public function hasShared($id) : bool
    {
        return $this->sharedRegistry->hasShared($id);
    }


    /**
     * @param string $id
     * @param mixed  $item
     *
     * @return static
     */
    public function set(string $id, $item)
    {
        if ($this->itemRegistry->hasItem($id)) {
            throw new OverflowException('Item is already defined: ' . $id);
        }

        $this->replace($id, $item);

        return $this;
    }

    /**
     * @param string          $id
     * @param string|\Closure $item
     *
     * @return static
     */
    public function replace(string $id, $item)
    {
        $this->itemRegistry->addItem($id, $item);

        return $this;
    }


    /**
     * @param string          $id
     * @param string|\Closure $bind
     * @param bool            $shared
     *
     * @return static
     */
    public function bind(string $id, $bind, bool $shared = false)
    {
        if ($this->bindRegistry->hasBind($id)) {
            throw new OverflowException('Bind is already defined: ' . $id);
        }

        $this->rebind($id, $bind, $shared);

        return $this;
    }

    /**
     * @param string          $id
     * @param string|\Closure $bind
     *
     * @return static
     */
    public function bindShared(string $id, $bind)
    {
        $this->bind($id, $bind, $shared = true);

        return $this;
    }


    /**
     * @param string          $id
     * @param string|\Closure $bind
     * @param bool            $shared
     *
     * @return static
     */
    public function rebind(string $id, $bind, bool $shared = false)
    {
        if ($id !== $bind) {
            $this->bindRegistry->setBind($id, $bind);
        }

        if (! $shared && $this->sharedRegistry->hasShared($id)) {
            $this->sharedRegistry->removeShared($id);

        } elseif ($shared && ! $this->sharedRegistry->hasShared($id)) {
            $this->sharedRegistry->setShared($id);

        }

        return $this;
    }

    /**
     * @param string $id
     * @param mixed  $bind
     *
     * @return static
     */
    public function rebindShared(string $id, $bind)
    {
        $this->rebind($id, $bind, $shared = true);

        return $this;
    }


    /**
     * @param string                   $id
     * @param string|callable|\Closure $func
     *
     * @return static
     */
    public function extend(string $id, $func)
    {
        $this->extendRegistry->addExtend($id, $func);

        return $this;
    }
}
