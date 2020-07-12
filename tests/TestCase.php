<?php

namespace Modstore\Cronski\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Modstore\Cronski\CronskiServiceProvider;

class TestCase extends OrchestraTestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app)
    {
        return [
            CronskiServiceProvider::class,
        ];
    }
}
