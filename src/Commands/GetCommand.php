<?php

declare(strict_types=1);

namespace Jeremy379\LaravelStorageCli\Commands;

use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class GetCommand extends StorageCommand
{
    protected $signature = 'storage:get
        {location : File path, optionally prefixed with disk:}
        {destination? : Local file or directory (defaults to the source filename)}
        {--d|disk= : Filesystem disk to use}
        {--force : Overwrite an existing local file}';

    protected $description = 'Download a file from a disk to the machine running Artisan';

    public function handle(): int
    {
        return $this->safely(function (): int {
            $target = $this->target($this->argument('location'))->requireFile();
            $destination = $this->argument('destination') ?: basename($target->path);

            if (is_dir($destination)) {
                $destination = rtrim($destination, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.basename($target->path);
            }

            $directory = dirname($destination);

            if (! is_dir($directory) || ! is_writable($directory)) {
                throw new RuntimeException('The destination directory does not exist or is not writable.');
            }

            if (file_exists($destination) && ! $this->option('force')) {
                throw new RuntimeException('The destination already exists; use --force to overwrite it.');
            }

            $source = Storage::disk($target->disk)->readStream($target->path);

            if (! is_resource($source)) {
                throw new RuntimeException('Unable to open the remote file.');
            }

            $temporary = null;

            try {
                $temporary = tempnam($directory, '.storage-cli-');

                if ($temporary === false) {
                    throw new RuntimeException('Unable to create a temporary local file.');
                }

                $output = fopen($temporary, 'wb');

                if ($output === false) {
                    throw new RuntimeException('Unable to write the temporary local file.');
                }

                try {
                    $bytes = stream_copy_to_stream($source, $output);

                    if ($bytes === false) {
                        throw new RuntimeException('The download did not complete.');
                    }
                } finally {
                    fclose($output);
                }

                if (! rename($temporary, $destination)) {
                    throw new RuntimeException('Unable to move the downloaded file into place.');
                }

                $this->info(sprintf('Downloaded %d bytes to %s', $bytes, $destination));

                return self::SUCCESS;
            } finally {
                fclose($source);

                if ($temporary !== null && is_file($temporary)) {
                    unlink($temporary);
                }
            }
        });
    }
}
