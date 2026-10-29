<?php

declare(strict_types=1);

namespace Jeremy379\LaravelStorageCli\Tests;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

final class CommandsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('filesystems.disks.fixture', [
            'driver' => 'local',
            'root' => storage_path('framework/testing/disks/fixture'),
        ]);

        Storage::fake('fixture');
        Storage::disk('fixture')->put('reports/hello.txt', 'Hello from the filesystem!');
        Storage::disk('fixture')->put('reports/2026-10-29T08:10:30.log', 'Timestamped file');
        Storage::disk('fixture')->put('reports/nested/second.txt', 'Nested file');
    }

    public function test_it_lists_configured_disks(): void
    {
        $this->assertSame(0, Artisan::call('storage:ls'));

        $this->assertStringContainsString('fixture', Artisan::output());
    }

    public function test_it_lists_files_and_directories_as_json(): void
    {
        $this->assertSame(0, Artisan::call('storage:ls', [
            'location' => 'fixture:reports',
            '--json' => true,
        ]));

        $entries = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);
        $paths = array_column($entries, 'path');

        $this->assertContains('reports/hello.txt', $paths);
        $this->assertContains('reports/nested', $paths);
        $this->assertNotContains('reports/nested/second.txt', $paths);
    }

    public function test_it_lists_recursively(): void
    {
        $this->assertSame(0, Artisan::call('storage:ls', [
            'location' => 'fixture:reports',
            '--recursive' => true,
        ]));

        $this->assertStringContainsString('reports/nested/second.txt', Artisan::output());
    }

    public function test_it_prints_file_contents(): void
    {
        $this->assertSame(0, Artisan::call('storage:cat', [
            'location' => 'fixture:reports/hello.txt',
        ]));

        $this->assertSame('Hello from the filesystem!', Artisan::output());
    }

    public function test_it_treats_the_location_as_literal_when_a_disk_option_is_used(): void
    {
        $this->assertSame(0, Artisan::call('storage:cat', [
            'location' => 'reports/2026-10-29T08:10:30.log',
            '--disk' => 'fixture',
        ]));

        $this->assertSame('Timestamped file', Artisan::output());
    }

    public function test_it_respects_cat_size_limit(): void
    {
        $this->assertSame(1, Artisan::call('storage:cat', [
            'location' => 'fixture:reports/hello.txt',
            '--max-bytes' => '5',
        ]));

        $this->assertStringContainsString('exceeds --max-bytes', Artisan::output());
    }

    public function test_it_generates_a_file_url(): void
    {
        $this->assertSame(0, Artisan::call('storage:url', [
            'location' => 'fixture:reports/hello.txt',
        ]));

        $this->assertStringContainsString('reports/hello.txt', Artisan::output());
    }

    public function test_it_provides_file_metadata_as_json(): void
    {
        $this->assertSame(0, Artisan::call('storage:stat', [
            'location' => 'fixture:reports/hello.txt',
            '--json' => true,
        ]));

        $data = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('fixture', $data['disk']);
        $this->assertSame('reports/hello.txt', $data['path']);
        $this->assertSame(strlen('Hello from the filesystem!'), $data['size']);
    }

    public function test_it_downloads_without_overwriting_unless_forced(): void
    {
        $directory = sys_get_temp_dir().'/laravel-storage-cli-'.bin2hex(random_bytes(6));
        mkdir($directory);
        $destination = $directory.'/download.txt';

        try {
            $arguments = [
                'location' => 'fixture:reports/hello.txt',
                'destination' => $destination,
            ];

            $this->assertSame(0, Artisan::call('storage:get', $arguments));
            $this->assertSame('Hello from the filesystem!', file_get_contents($destination));

            file_put_contents($destination, 'Keep me');

            $this->assertSame(1, Artisan::call('storage:get', $arguments));
            $this->assertSame('Keep me', file_get_contents($destination));

            $this->assertSame(0, Artisan::call('storage:get', $arguments + ['--force' => true]));
            $this->assertSame('Hello from the filesystem!', file_get_contents($destination));
        } finally {
            if (is_file($destination)) {
                unlink($destination);
            }

            rmdir($directory);
        }
    }
}
