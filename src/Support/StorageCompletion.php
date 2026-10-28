<?php

declare(strict_types=1);

namespace Jeremy379\LaravelStorageCli\Support;

use Illuminate\Support\Facades\Storage;
use Throwable;

final class StorageCompletion
{
    private const MAX_SUGGESTIONS = 50;

    private const MAX_SCANNED_ENTRIES = 500;

    public static function disks(string $prefix = ''): array
    {
        $disks = config('filesystems.disks', []);

        if (! is_array($disks)) {
            return [];
        }

        $names = array_filter(
            array_keys($disks),
            fn (string $name): bool => str_starts_with($name, $prefix),
        );

        sort($names, SORT_STRING);

        return array_values($names);
    }

    public static function paths(string $partial, ?string $diskOption = null): array
    {
        $diskSuggestions = [];

        if (($diskOption === null || $diskOption === '') && ! str_contains($partial, ':')) {
            $diskSuggestions = array_map(
                fn (string $name): string => $name.':',
                self::disks($partial),
            );
        }

        try {
            $target = DiskPath::parse($partial, $diskOption);
            $colon = strpos($partial, ':');
            $prefix = $colon === false ? '' : substr($partial, 0, $colon + 1);

            $slash = strrpos($target->path, '/');
            $directory = $slash === false ? '' : substr($target->path, 0, $slash);
            $namePrefix = $slash === false ? $target->path : substr($target->path, $slash + 1);

            $matches = [];
            $scanned = 0;

            foreach (Storage::disk($target->disk)->getDriver()->listContents($directory, false) as $entry) {
                if (++$scanned > self::MAX_SCANNED_ENTRIES) {
                    break;
                }

                if (! str_starts_with(basename($entry->path()), $namePrefix)) {
                    continue;
                }

                $matches[] = $prefix.$entry->path().($entry->isDir() ? '/' : '');

                if (count($matches) >= self::MAX_SUGGESTIONS) {
                    break;
                }
            }

            sort($matches, SORT_STRING);

            return array_slice(array_values(array_unique([
                ...$diskSuggestions,
                ...$matches,
            ])), 0, self::MAX_SUGGESTIONS);
        } catch (Throwable) {
            // Completion should never interrupt the user's shell if a disk is unavailable.
            return $diskSuggestions;
        }
    }
}
