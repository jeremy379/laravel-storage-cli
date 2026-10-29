<?php

declare(strict_types=1);

namespace Jeremy379\LaravelStorageCli\Commands;

use Illuminate\Support\Facades\Storage;
use Jeremy379\LaravelStorageCli\Exceptions\StorageCliException;

final class UrlCommand extends StorageCommand
{
    protected $signature = 'storage:url
        {location : File path, optionally prefixed with disk:}
        {--d|disk= : Filesystem disk to use}
        {--expires= : Generate a temporary URL valid for this many minutes}';

    protected $description = 'Print a public URL or a temporary signed URL for a file';

    public function handle(): int
    {
        return $this->safely(function (): int {
            $target = $this->target($this->argument('location'))->requireFile();
            $filesystem = Storage::disk($target->disk);
            $minutes = $this->option('expires');

            if ($minutes === null) {
                $this->line($filesystem->url($target->path));

                return self::SUCCESS;
            }

            if (! ctype_digit((string) $minutes) || (int) $minutes < 1) {
                throw new StorageCliException('--expires must be a positive number of minutes.');
            }

            if (! $filesystem->providesTemporaryUrls()) {
                throw new StorageCliException('The selected disk does not support temporary URLs.');
            }

            $this->line($filesystem->temporaryUrl($target->path, now()->addMinutes((int) $minutes)));

            return self::SUCCESS;
        });
    }
}
