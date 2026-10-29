<?php

declare(strict_types=1);

namespace Jeremy379\LaravelStorageCli\Tests;

use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Jeremy379\LaravelStorageCli\Support\DiskPath;

final class DiskPathTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('filesystems.disks.fixture', [
            'driver' => 'local',
            'root' => storage_path('framework/testing/disks/fixture'),
        ]);

        Storage::fake('fixture');
    }

    public function test_it_parses_a_disk_prefix(): void
    {
        $target = DiskPath::parse('fixture:reports/2026.csv');

        $this->assertSame('fixture', $target->disk);
        $this->assertSame('reports/2026.csv', $target->path);
    }

    public function test_it_accepts_the_disk_option(): void
    {
        $target = DiskPath::parse('reports/file.csv', 'fixture');

        $this->assertSame('fixture', $target->disk);
        $this->assertSame('reports/file.csv', $target->path);
    }

    public function test_an_explicit_disk_treats_a_colon_as_part_of_the_path(): void
    {
        $target = DiskPath::parse('archive:report.csv', 'fixture');

        $this->assertSame('fixture', $target->disk);
        $this->assertSame('archive:report.csv', $target->path);
    }

    public function test_it_rejects_parent_directory_traversal(): void
    {
        $this->expectException(InvalidArgumentException::class);

        DiskPath::parse('fixture:reports/../../.env');
    }

    public function test_it_rejects_unknown_disks(): void
    {
        $this->expectException(InvalidArgumentException::class);

        DiskPath::parse('not-a-disk:file.csv');
    }
}
