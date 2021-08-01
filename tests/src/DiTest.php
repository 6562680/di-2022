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
        // create instance
        $di = new Di();

        // static singleton
        // $di = Di::getInstance(); // get
        // Di::setInstance($di); // replace

        // run register() for provider
        $di->register(BDeferableProvider::class);
        $di->register(new BBBootableProvider($di));

        // copy `sources` (vendor) files/directories of to `targets` (application)
        $di->discover();

        // update discovered config like in production
        $provider = $di->getProvider(BBBootableProvider::class);
        file_put_contents($provider->path('config'), '<?php return [ 3, 2, 1 ];');

        // run boot() for all registered bootable providers
        $di->boot();

        // get instances (this also run boot() for deferable providers)
        $b1 = $di->get(BInterface::class);
        $b2 = $di->get(BInterface::class);
        $bb = $di->get(BBInterface::class);

        // check instances
        $this->assertInstanceOf(B::class, $b1);
        $this->assertInstanceOf(B::class, $b2);
        $this->assertInstanceOf(BB::class, $bb);

        // check discovered config (we updated it above)
        $this->assertEquals([ 3, 2, 1 ], $bb->getConfig());

        // check singleton
        $this->assertEquals($b1, $b2);

        // call method or function
        $fn = function (B $b, $hello, BBInterface $bb, $world) {
            return func_get_args();
        };
        $params = [
            '$hello' => 1,
            '$world' => 2,

            // > keys could be `binds`
            // BInterface::class => $b1,
            // BBInterface::class => $bb,

            // > keys could be argument names prefixed with $
            // '$b' => $b1,
            // '$bb' => $bb,

            // > keys could be argument positions (integers)
            // 0 => $b1,
            // 2 => $bb
        ];
        $result = $di->call($fn, $params);

        // check arguments
        $this->assertEquals([ $b1, 1, $bb, 2 ], $result);
    }
}
