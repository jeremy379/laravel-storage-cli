<?php

declare(strict_types=1);

namespace Jeremy379\LaravelStorageCli\Tests;

use Jeremy379\LaravelStorageCli\StorageCliServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [StorageCliServiceProvider::class];
    }
}
