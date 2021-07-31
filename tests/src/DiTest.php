<?php

namespace Gzhegow\Di\Tests;

use Gzhegow\Di\Di;
use PHPUnit\Framework\TestCase;
use Gzhegow\Di\Tests\Classes\B;
use Gzhegow\Di\Tests\Classes\BB;
use Gzhegow\Di\Tests\Classes\BInterface;
use Gzhegow\Di\Tests\Classes\BBInterface;
use Gzhegow\Di\Tests\Classes\BBBootableProvider;
use Gzhegow\Di\Tests\Classes\BDeferableProvider;


/**
 * DiTest
 */
class DiTest extends TestCase
{
    public function testDi()
    {
        $di = new Di();

        $di->register(BDeferableProvider::class);
        $di->register(BBBootableProvider::class);

        $di->boot(); // run boot for bootable providers
        $di->discover(); // copy source files to targets

        $b1 = $di->get(BInterface::class); // also run boot for deferable provider
        $b2 = $di->get(BInterface::class); // also run boot for deferable provider

        $bb = $di->get(BBInterface::class);

        $this->assertInstanceOf(B::class, $b1);
        $this->assertInstanceOf(B::class, $b2);

        $this->assertEquals($b1, $b2); // check singleton

        $this->assertInstanceOf(BB::class, $bb);
    }
}
