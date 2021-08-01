# Di

Микро-контейнер с инжектором зависимостей

```php
<?php

namespace Gzhegow\Di;

use Gzhegow\Di\Tests\Classes\B;
use Gzhegow\Di\Tests\Classes\BB;
use Gzhegow\Di\Tests\Classes\BInterface;
use Gzhegow\Di\Tests\Classes\BBInterface;
use Gzhegow\Di\Tests\Classes\BDeferableProvider;
use Gzhegow\Di\Tests\Classes\BBBootableProvider;

// create instance
$di = new Di();

// static singleton
// $di = Di::getInstance(); // get
// Di::setInstance($di); // replace

// run register() for provider
$di->register(BDeferableProvider::class);
$di->register(BBBootableProvider::class);

// copy `sources` files/directories of the module to `targets`
$di->discover();

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

// check singleton
$this->assertEquals($b1, $b2);

// call method or function
$fn = function (B $a, $hello, BB $b, $world) {
    return func_get_args();
};
$params = [
    '$hello' => 1,
    '$world' => 2,
];
$result = $di->call($fn, $params);

// check arguments
$this->assertEquals([ $b1, 1, $bb, 2 ], $result);
```
