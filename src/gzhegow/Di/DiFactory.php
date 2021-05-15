<?php

namespace Gzhegow\Di;

use Gzhegow\Support\Str as SupportStr;
use Gzhegow\Di\Core\Assert;
use Gzhegow\Di\Domain\Node\Node;
use Gzhegow\Reflection\Reflection;
use Gzhegow\Di\Core\AssertInterface;
use Gzhegow\Support\Php as SupportPhp;
use Gzhegow\Support\Arr as SupportArr;
use Psr\Container\ContainerInterface;
use Gzhegow\Support\Type as SupportType;
use Gzhegow\Di\Domain\Injector\Injector;
use Gzhegow\Di\Domain\Provider\Provider;
use Gzhegow\Di\Domain\Container\Container;
use Gzhegow\Support\Filter as SupportFilter;
use Gzhegow\Reflection\ReflectionInterface;
use Gzhegow\Di\Domain\Registry\BindRegistry;
use Gzhegow\Di\Domain\Registry\ItemRegistry;
use Gzhegow\Di\Domain\Registry\SharedRegistry;
use Gzhegow\Di\Domain\Registry\ExtendRegistry;
use Gzhegow\Di\Domain\Provider\ProviderManager;
use Gzhegow\Di\Domain\Delegate\DelegateManager;
use Gzhegow\Di\Domain\Node\NodeFactoryInterface;
use Gzhegow\Di\Domain\Registry\DeferableRegistry;
use Gzhegow\Di\Domain\Injector\InjectorInterface;
use Gzhegow\Di\Domain\Provider\ProviderInterface;
use Gzhegow\Di\Domain\Provider\ProviderFactoryInterface;


/**
 * DiFactory
 */
class DiFactory implements
    DiFactoryInterface,
    NodeFactoryInterface,
    ProviderFactoryInterface
{
    /**
     * @var ContainerInterface
     */
    protected $proxy;

    /**
     * @var null|SupportArr
     */
    protected $supportArr;
    /**
     * @var null|SupportFilter
     */
    protected $supportFilter;
    /**
     * @var null|SupportPhp
     */
    protected $supportPhp;
    /**
     * @var null|SupportType
     */
    protected $supportType;

    /**
     * @var null|ReflectionInterface
     */
    protected $reflection;

    /**
     * @var null|AssertInterface
     */
    protected $assert;

    /**
     * @var null|NodeFactoryInterface
     */
    protected $nodeFactory;
    /**
     * @var null|ProviderFactoryInterface
     */
    protected $providerFactory;

    /**
     * @var null|BindRegistry
     */
    protected $bindRegistry;
    /**
     * @var null|DeferableRegistry
     */
    protected $deferableRegistry;
    /**
     * @var null|ExtendRegistry
     */
    protected $extendRegistry;
    /**
     * @var null|ItemRegistry
     */
    protected $itemRegistry;
    /**
     * @var null|SharedRegistry
     */
    protected $sharedRegistry;

    /**
     * @var ContainerInterface
     */
    protected $container;
    /**
     * @var null|InjectorInterface
     */
    protected $injector;

    /**
     * @var null|DiManager
     */
    protected $diManager;
    /**
     * @var null|DelegateManager
     */
    protected $delegateManager;
    /**
     * @var null|ProviderManager
     */
    protected $providerManager;


    /**
     * Constructor
     *
     * @param null|ContainerInterface       $proxy
     *
     * @param null|ReflectionInterface      $reflection
     *
     * @param null|SupportArr               $supportArr
     * @param null|SupportFilter            $supportFilter
     * @param null|SupportPhp               $supportPhp
     * @param null|SupportType              $supportType
     *
     * @param null|AssertInterface          $assert
     *
     * @param null|NodeFactoryInterface     $nodeFactory
     * @param null|ProviderFactoryInterface $providerFactory
     *
     * @param null|BindRegistry             $bindRegistry
     * @param null|DeferableRegistry        $deferableRegistry
     * @param null|ExtendRegistry           $extendRegistry
     * @param null|ItemRegistry             $itemRegistry
     * @param null|SharedRegistry           $sharedRegistry
     *
     * @param null|InjectorInterface        $injector
     *
     * @param null|DiManager                $diManager
     * @param null|DelegateManager          $delegateManager
     * @param null|ProviderManager          $providerManager
     */
    public function __construct(
        ContainerInterface $proxy = null,

        ReflectionInterface $reflection = null,

        SupportArr $supportArr = null,
        SupportFilter $supportFilter = null,
        SupportPhp $supportPhp = null,
        SupportType $supportType = null,

        AssertInterface $assert = null,

        NodeFactoryInterface $nodeFactory = null,
        ProviderFactoryInterface $providerFactory = null,

        BindRegistry $bindRegistry = null,
        DeferableRegistry $deferableRegistry = null,
        ExtendRegistry $extendRegistry = null,
        ItemRegistry $itemRegistry = null,
        SharedRegistry $sharedRegistry = null,

        InjectorInterface $injector = null,

        DiManager $diManager = null,
        DelegateManager $delegateManager = null,
        ProviderManager $providerManager = null
    )
    {
        $this->proxy = $proxy;

        $this->reflection = $reflection ?? $this->loadReflection();

        $this->supportArr = $supportArr ?? $this->loadSupportArr();
        $this->supportFilter = $supportFilter ?? $this->loadSupportFilter();
        $this->supportPhp = $supportPhp ?? $this->loadSupportPhp();
        $this->supportType = $supportType ?? $this->loadSupportType();

        $this->assert = $assert ?? $this->loadAssert();

        $this->nodeFactory = $nodeFactory ?? $this->loadNodeFactory();
        $this->providerFactory = $providerFactory ?? $this->loadProviderFactory();

        $this->bindRegistry = $bindRegistry ?? $this->loadBindRegistry();
        $this->deferableRegistry = $deferableRegistry ?? $this->loadDeferableRegistry();
        $this->extendRegistry = $extendRegistry ?? $this->loadExtendRegistry();
        $this->itemRegistry = $itemRegistry ?? $this->loadItemRegistry();
        $this->sharedRegistry = $sharedRegistry ?? $this->loadSharedRegistry();

        $this->container = $proxy ?? $this->loadContainer(); // ! proxy could be a parent
        $this->injector = $injector ?? $this->loadInjector();

        $this->diManager = $diManager ?? $this->loadDiManager();
        $this->delegateManager = $delegateManager ?? $this->loadDelegateManager();
        $this->providerManager = $providerManager ?? $this->loadProviderManager();
    }


    /**
     * @return Di
     */
    public function newDi() : Di
    {
        $di = new Di(
            $this->loadReflection(),

            $this->loadNodeFactory(),

            $this->loadContainer(),
            $this->loadInjector(),

            $this->loadDiManager(),
            $this->loadProviderManager(),
            $this->loadDelegateManager(),
        );

        return $di;
    }


    /**
     * @param string    $any
     *
     * @param null|Node $parent
     *
     * @return Node
     */
    public function newNode($any, Node $parent = null) : Node
    {
        $node = new Node(
            $this->loadReflection(),

            $this->loadSupportArr(),
            $this->loadSupportPhp(),
            $this->loadSupportType(),

            $this->loadNodeFactory(),

            $this->loadBindRegistry(),
            $this->loadExtendRegistry(),
            $this->loadItemRegistry(),
            $this->loadSharedRegistry(),

            $this->loadDelegateManager(),
            $this->loadProviderManager(),

            $any
        );

        if ($parent) {
            $node->setParent($parent);
        }

        return $node;
    }

    /**
     * @param string $any
     *
     * @return Node
     */
    public function newRootNode($any) : Node
    {
        $node = $this->newNode($any);

        return $node;
    }


    /**
     * @param string $name
     *
     * @return Provider
     */
    public function newProvider(string $name) : ProviderInterface
    {
        return null
            ?? $this->getProxy($name)
            ?? $this->get($name);
    }


    /**
     * @return SupportArr
     */
    protected function loadSupportArr() : SupportArr
    {
        return $this->arr
            ?? $this->getProxy(SupportArr::class)
            ?? new SupportArr(
                $this->loadSupportFilter(),
                $this->loadSupportPhp(),
                $this->loadSupportStr(),
            );
    }

    /**
     * @return SupportFilter
     */
    protected function loadSupportFilter() : SupportFilter
    {
        return $this->supportFilter
            ?? $this->getProxy(SupportFilter::class)
            ?? new SupportFilter();
    }

    /**
     * @return SupportPhp
     */
    protected function loadSupportPhp() : SupportPhp
    {
        return $this->supportPhp
            ?? $this->getProxy(SupportPhp::class)
            ?? new SupportPhp(
                $this->loadSupportFilter()
            );
    }

    /**
     * @return SupportStr
     */
    protected function loadSupportStr() : SupportStr
    {
        return $this->supportStr
            ?? $this->getProxy(SupportStr::class)
            ?? new SupportStr(
                $this->loadSupportFilter()
            );
    }

    /**
     * @return SupportType
     */
    protected function loadSupportType() : SupportType
    {
        return $this->supportType
            ?? $this->getProxy(SupportType::class)
            ?? new SupportType(
                $this->loadSupportFilter()
            );
    }


    /**
     * @return ReflectionInterface
     */
    protected function loadReflection() : ReflectionInterface
    {
        return $this->reflection
            ?? $this->getProxy(ReflectionInterface::class)
            ?? new Reflection(
                $this->loadSupportFilter(),
                $this->loadSupportPhp()
            );
    }


    /**
     * @return AssertInterface
     */
    protected function loadAssert() : AssertInterface
    {
        return $this->assert
            ?? $this->getProxy(AssertInterface::class)
            ?? new Assert(
                $this->loadSupportType()
            );
    }


    /**
     * @return NodeFactoryInterface
     */
    protected function loadNodeFactory() : NodeFactoryInterface
    {
        return $this->nodeFactory
            ?? $this->getProxy(NodeFactoryInterface::class)
            ?? $this;
    }

    /**
     * @return ProviderFactoryInterface
     */
    protected function loadProviderFactory() : ProviderFactoryInterface
    {
        return $this->providerFactory
            ?? $this->getProxy(ProviderFactoryInterface::class)
            ?? $this;
    }


    /**
     * @return BindRegistry
     */
    protected function loadBindRegistry() : BindRegistry
    {
        return $this->bindRegistry
            ?? $this->getProxy(BindRegistry::class)
            ?? new BindRegistry(
                $this->loadAssert()
            );
    }

    /**
     * @return DeferableRegistry
     */
    protected function loadDeferableRegistry() : DeferableRegistry
    {
        return $this->deferableRegistry
            ?? $this->getProxy(DeferableRegistry::class)
            ?? new DeferableRegistry(
                $this->loadAssert()
            );
    }

    /**
     * @return ExtendRegistry
     */
    protected function loadExtendRegistry() : ExtendRegistry
    {
        return $this->extendRegistry
            ?? $this->getProxy(ExtendRegistry::class)
            ?? new ExtendRegistry(
                $this->loadAssert()
            );
    }

    /**
     * @return ItemRegistry
     */
    protected function loadItemRegistry() : ItemRegistry
    {
        return $this->itemRegistry
            ?? $this->getProxy(ItemRegistry::class)
            ?? new ItemRegistry(
                $this->loadAssert()
            );
    }

    /**
     * @return SharedRegistry
     */
    protected function loadSharedRegistry() : SharedRegistry
    {
        return $this->sharedRegistry
            ?? $this->getProxy(SharedRegistry::class)
            ?? new SharedRegistry(
                $this->loadAssert()
            );
    }


    /**
     * @return ContainerInterface
     */
    protected function loadContainer() : ContainerInterface
    {
        return $this->container
            ?? $this->getProxy(ContainerInterface::class)
            ?? new Container(
                $this->loadSupportType(),

                $this->loadNodeFactory(),

                $this->loadBindRegistry(),
                $this->loadItemRegistry(),
            );
    }

    /**
     * @return InjectorInterface
     */
    protected function loadInjector() : InjectorInterface
    {
        return $this->injector
            ?? $this->getProxy(InjectorInterface::class)
            ?? new Injector(
                $this->loadNodeFactory()
            );
    }


    /**
     * @return DiManager
     */
    protected function loadDiManager() : DiManager
    {
        return $this->diManager
            ?? $this->getProxy(DiManager::class)
            ?? new DiManager(
                $this->loadNodeFactory(),
                $this->loadBindRegistry(),
                $this->loadExtendRegistry(),
                $this->loadItemRegistry(),
                $this->loadSharedRegistry()
            );
    }

    /**
     * @return DelegateManager
     */
    protected function loadDelegateManager() : DelegateManager
    {
        return $this->delegateManager
            ?? $this->getProxy(DelegateManager::class)
            ?? new DelegateManager(
                $this->loadAssert()
            );
    }

    /**
     * @return ProviderManager
     */
    protected function loadProviderManager() : ProviderManager
    {
        return $this->providerManager
            ?? $this->getProxy(ProviderManager::class)
            ?? new ProviderManager(
                $this->loadAssert(),

                $this->loadProviderFactory(),

                $this->loadDeferableRegistry()
            );
    }


    /**
     * @param string $id
     *
     * @return null|mixed
     */
    protected function get(string $id)
    {
        return $this->container && $this->container->has($id)
            ? $this->container->get($id)
            : null;
    }

    /**
     * @param string $id
     *
     * @return null|mixed
     */
    protected function getProxy(string $id)
    {
        return $this->proxy && $this->proxy->has($id)
            ? $this->proxy->get($id)
            : null;
    }
}
