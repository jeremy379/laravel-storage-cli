<?php

declare(strict_types=1);

namespace Jeremy379\LaravelStorageCli\Commands;

use Illuminate\Support\Facades\Storage;
use Jeremy379\LaravelStorageCli\Exceptions\StorageCliException;
use League\Flysystem\FilesystemException;

final class StatCommand extends StorageCommand
{
    protected $signature = 'storage:stat
        {location : File path, optionally prefixed with disk:}
        {--d|disk= : Filesystem disk to use}
        {--json : Output machine-readable JSON}';

    protected $description = 'Show file metadata from a filesystem disk';

    public function handle(): int
    {
        return $this->safely(function (): int {
            $target = $this->target($this->argument('location'))->requireFile();
            $filesystem = Storage::disk($target->disk);

            if (! $filesystem->exists($target->path)) {
                throw new StorageCliException('The file does not exist.');
            }

            $metadata = [
                'disk' => $target->disk,
                'path' => $target->path,
                'size' => $filesystem->size($target->path),
                'mime_type' => $filesystem->mimeType($target->path),
                'last_modified' => gmdate('c', $filesystem->lastModified($target->path)),
                'visibility' => null,
            ];

            try {
                $metadata['visibility'] = $filesystem->visibility($target->path);
            } catch (FilesystemException) {
                // Some filesystem adapters do not expose visibility.
            }

            if ($this->option('json')) {
                $this->line(json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

                return self::SUCCESS;
            }

            foreach ($metadata as $key => $value) {
                $this->line(sprintf('%-15s %s', $key.':', $value === null ? '-' : $value));
            }

            return self::SUCCESS;
        });
    }
}
