<?php

namespace Gzhegow\Di\Tests\Classes;


/**
 * BB
 */
class BB implements BBInterface
{
    /**
     * @var A
     */
    protected $a;
    /**
     * @var mixed
     */
    protected $hello;
    /**
     * @var B
     */
    protected $b;
    /**
     * @var mixed
     */
    protected $world;

    /**
     * @var array
     */
    protected $config;


    /**
     * Constructor
     *
     * @param A     $a
     * @param mixed $hello
     * @param B     $b
     * @param mixed $world
     * @param array $config
     */
    public function __construct(A $a, $hello, B $b, $world,
        array $config = []
    )
    {
        $this->a = $a;
        $this->hello = $hello;
        $this->b = $b;
        $this->world = $world;

        $this->config = $config;
    }


    /**
     * @return array
     */
    public function getConfig() : array
    {
        return $this->config;
    }
}
