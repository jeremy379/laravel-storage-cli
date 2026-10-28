<?php

declare(strict_types=1);

namespace Jeremy379\LaravelStorageCli\Commands;

use Illuminate\Console\Command;
use Jeremy379\LaravelStorageCli\Support\DiskPath;
use Throwable;

abstract class StorageCommand extends Command
{
    protected function target(?string $location): DiskPath
    {
        return DiskPath::parse($location, $this->option('disk'));
    }

    protected function safely(callable $operation): int
    {
        try {
            return $operation();
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
