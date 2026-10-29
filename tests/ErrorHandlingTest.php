<?php

declare(strict_types=1);

namespace Jeremy379\LaravelStorageCli\Tests;

use Jeremy379\LaravelStorageCli\Commands\StorageCommand;
use TypeError;

final class ErrorHandlingTest extends TestCase
{
    public function test_unexpected_errors_are_not_converted_to_command_failures(): void
    {
        $command = new class extends StorageCommand
        {
            protected $signature = 'storage:test-unexpected-error';

            public function handle(): int
            {
                return $this->safely(
                    static fn (): int => throw new TypeError('Unexpected failure.'),
                );
            }
        };

        $this->expectException(TypeError::class);

        $command->handle();
    }
}
