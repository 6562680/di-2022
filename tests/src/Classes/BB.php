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
     * Constructor
     *
     * @param A $a
     * @param   $hello
     * @param B $b
     * @param   $world
     */
    public function __construct(A $a, $hello, B $b, $world)
    {
        $this->a = $a;
        $this->hello = $hello;
        $this->b = $b;
        $this->world = $world;
    }
}
