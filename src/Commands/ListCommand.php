<?php

declare(strict_types=1);

namespace Jeremy379\LaravelStorageCli\Commands;

use Illuminate\Support\Facades\Storage;
use League\Flysystem\FileAttributes;

final class ListCommand extends StorageCommand
{
    protected $signature = 'storage:ls
        {location? : Directory, optionally prefixed with disk:}
        {--d|disk= : Filesystem disk to use}
        {--R|recursive : Include nested directories}
        {--l|long : Show type, size, and modification time}
        {--json : Output machine-readable JSON}';

    protected $description = 'List configured disks or files and directories on a disk';

    public function handle(): int
    {
        return $this->safely(function (): int {
            $location = $this->argument('location');

            if ($location === null && ! $this->option('disk')) {
                return $this->listDisks();
            }

            $target = $this->target($location);
            $listing = Storage::disk($target->disk)
                ->getDriver()
                ->listContents($target->path, (bool) $this->option('recursive'));

            $entries = [];

            foreach ($listing as $entry) {
                $entries[] = [
                    'path' => $entry->path(),
                    'type' => $entry->isDir() ? 'directory' : 'file',
                    'size' => $entry instanceof FileAttributes ? $entry->fileSize() : null,
                    'last_modified' => $entry->lastModified(),
                ];
            }

            usort($entries, fn (array $a, array $b): int => strcmp($a['path'], $b['path']));

            if ($this->option('json')) {
                $this->line(json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

                return self::SUCCESS;
            }

            foreach ($entries as $entry) {
                if (! $this->option('long')) {
                    $this->line($entry['path']);

                    continue;
                }

                $this->line(sprintf(
                    '%-4s %12s %-19s %s',
                    $entry['type'] === 'directory' ? 'dir' : 'file',
                    $entry['size'] === null ? '-' : (string) $entry['size'],
                    $entry['last_modified'] === null ? '-' : gmdate('Y-m-d H:i:s', $entry['last_modified']),
                    $entry['path'],
                ));
            }

            return self::SUCCESS;
        });
    }

    private function listDisks(): int
    {
        $disks = config('filesystems.disks', []);
        ksort($disks);
        $entries = [];

        foreach ($disks as $name => $configuration) {
            $entries[] = [
                'name' => $name,
                'driver' => $configuration['driver'] ?? 'unknown',
            ];
        }

        if ($this->option('json')) {
            $this->line(json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        } else {
            foreach ($entries as $entry) {
                $this->line(sprintf('%-20s %s', $entry['name'], $entry['driver']));
            }
        }

        return self::SUCCESS;
    }
}
