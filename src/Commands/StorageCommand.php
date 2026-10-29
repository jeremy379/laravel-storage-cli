<?php

declare(strict_types=1);

namespace Jeremy379\LaravelStorageCli\Commands;

use Illuminate\Console\Command;
use InvalidArgumentException;
use Jeremy379\LaravelStorageCli\Exceptions\StorageCliException;
use Jeremy379\LaravelStorageCli\Support\DiskPath;
use Jeremy379\LaravelStorageCli\Support\StorageCompletion;
use JsonException;
use League\Flysystem\FilesystemException;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;

abstract class StorageCommand extends Command
{
    public function complete(CompletionInput $input, CompletionSuggestions $suggestions): void
    {
        if ($input->mustSuggestOptionValuesFor('disk')) {
            $suggestions->suggestValues(StorageCompletion::disks($input->getCompletionValue()));

            return;
        }

        if ($input->mustSuggestArgumentValuesFor('location')) {
            $suggestions->suggestValues(StorageCompletion::paths(
                $input->getCompletionValue(),
                $input->getOption('disk'),
            ));

            return;
        }

        parent::complete($input, $suggestions);
    }

    protected function target(?string $location): DiskPath
    {
        return DiskPath::parse($location, $this->option('disk'));
    }

    protected function safely(callable $operation): int
    {
        try {
            return $operation();
        } catch (FilesystemException|InvalidArgumentException|JsonException|StorageCliException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
