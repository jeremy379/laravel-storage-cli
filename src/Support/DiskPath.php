<?php

declare(strict_types=1);

namespace Jeremy379\LaravelStorageCli\Support;

use InvalidArgumentException;

final readonly class DiskPath
{
    public function __construct(
        public string $disk,
        public string $path,
    ) {
    }

    public static function parse(?string $location, ?string $diskOption = null): self
    {
        $path = $location ?? '';

        if (preg_match('/^([A-Za-z0-9_.-]+):(.*)$/s', $path, $matches) === 1) {
            if ($diskOption !== null && $diskOption !== '' && $diskOption !== $matches[1]) {
                throw new InvalidArgumentException('The disk in the path conflicts with --disk.');
            }

            $diskOption = $matches[1];
            $path = $matches[2];
        }

        $disk = $diskOption ?: config('filesystems.default', 'local');
        $disks = config('filesystems.disks', []);

        if (! is_string($disk) || ! is_array($disks) || ! array_key_exists($disk, $disks)) {
            throw new InvalidArgumentException(sprintf('Unknown filesystem disk: %s', (string) $disk));
        }

        $path = ltrim(str_replace('\\', '/', $path), '/');

        if (str_contains($path, "\0") || preg_match('~(^|/)\.\.(/|$)~', $path) === 1) {
            throw new InvalidArgumentException('Paths must stay within the selected filesystem disk.');
        }

        return new self($disk, $path);
    }

    public function requireFile(): self
    {
        if ($this->path === '' || str_ends_with($this->path, '/')) {
            throw new InvalidArgumentException('A file path is required.');
        }

        return $this;
    }
}
