<?php

declare(strict_types=1);

namespace Jeremy379\LaravelStorageCli;

use Illuminate\Support\ServiceProvider;
use Jeremy379\LaravelStorageCli\Commands\CatCommand;
use Jeremy379\LaravelStorageCli\Commands\GetCommand;
use Jeremy379\LaravelStorageCli\Commands\ListCommand;
use Jeremy379\LaravelStorageCli\Commands\StatCommand;
use Jeremy379\LaravelStorageCli\Commands\UrlCommand;

final class StorageCliServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            ListCommand::class,
            CatCommand::class,
            UrlCommand::class,
            GetCommand::class,
            StatCommand::class,
        ]);
    }
}
