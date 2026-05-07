<?php

declare(strict_types=1);

namespace LaravelZohoMcp\Tests;

use LaravelZohoMcp\ZohoMcpServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [ZohoMcpServiceProvider::class];
    }
}
