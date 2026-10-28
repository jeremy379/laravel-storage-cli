<?php

declare(strict_types=1);

namespace Jeremy379\LaravelStorageCli\Commands;

use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;

final class CatCommand extends StorageCommand
{
    protected $signature = 'storage:cat
        {location : File path, optionally prefixed with disk:}
        {--d|disk= : Filesystem disk to use}
        {--max-bytes=1048576 : Maximum file size to print unless --force is supplied}
        {--force : Allow output of files larger than the limit}';

    protected $description = 'Print a file from a filesystem disk to standard output';

    public function handle(): int
    {
        return $this->safely(function (): int {
            $target = $this->target($this->argument('location'))->requireFile();
            $filesystem = Storage::disk($target->disk);
            $limit = filter_var($this->option('max-bytes'), FILTER_VALIDATE_INT);

            if ($limit === false || $limit < 1) {
                throw new RuntimeException('--max-bytes must be a positive integer.');
            }

            if (! $this->option('force') && $filesystem->size($target->path) > $limit) {
                throw new RuntimeException('File exceeds --max-bytes; increase the limit or use --force.');
            }

            $stream = $filesystem->readStream($target->path);

            if (! is_resource($stream)) {
                throw new RuntimeException('Unable to open the remote file.');
            }

            try {
                while (! feof($stream)) {
                    $chunk = fread($stream, 8192);

                    if ($chunk === false) {
                        throw new RuntimeException('Failed while reading the remote file.');
                    }

                    $this->output->write($chunk, false, OutputInterface::OUTPUT_RAW);
                }
            } finally {
                fclose($stream);
            }

            return self::SUCCESS;
        });
    }
}
