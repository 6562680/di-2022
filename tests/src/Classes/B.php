<?php

namespace Gzhegow\Di\Tests\Classes;


/**
 * B
 */
class B implements BInterface
{
    /**
     * @var A
     */
    protected $a;

    public function __construct(A $a)
    {
        $this->a = $a;
    }
}
