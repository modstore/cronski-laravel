<?php

namespace Modstore\Cronski\Tests;

use Orchestra\Testbench\TestCase;
use Modstore\Cronski\CronskiServiceProvider;

class ExampleTest extends TestCase
{

    protected function getPackageProviders($app)
    {
        return [CronskiServiceProvider::class];
    }
    
    /** @test */
    public function true_is_true()
    {
        $this->assertTrue(true);
    }
}
