<?php

declare(strict_types=1);

namespace Jeremy379\LaravelStorageCli\Tests;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Jeremy379\LaravelStorageCli\Support\StorageCompletion;
use Symfony\Component\Console\Tester\CommandCompletionTester;

final class CompletionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('filesystems.disks.fixture', [
            'driver' => 'local',
            'root' => storage_path('framework/testing/disks/fixture'),
        ]);

        Storage::fake('fixture');
        Storage::disk('fixture')->put('reports/hello.txt', 'Hello');
        Storage::disk('fixture')->put('reports/2026-10-29T08:10:30.log', 'Timestamped file');
        Storage::disk('fixture')->put('reports/nested/other.txt', 'Other');
    }

    public function test_it_suggests_disk_names(): void
    {
        $this->assertContains('fixture', StorageCompletion::disks('fix'));
        $this->assertContains('fixture:', StorageCompletion::paths('fix'));
    }

    public function test_it_suggests_folders_and_files(): void
    {
        $this->assertContains('fixture:reports/hello.txt', StorageCompletion::paths('fixture:reports/h'));
        $this->assertContains('fixture:reports/nested/', StorageCompletion::paths('fixture:reports/n'));
    }

    public function test_it_uses_the_selected_disk(): void
    {
        $this->assertContains('reports/hello.txt', StorageCompletion::paths('reports/h', 'fixture'));
    }

    public function test_it_completes_colon_paths_on_an_explicit_disk(): void
    {
        $this->assertContains(
            'reports/2026-10-29T08:10:30.log',
            StorageCompletion::paths('reports/2026-10-29T08:', 'fixture'),
        );
    }

    public function test_it_ignores_unavailable_disks(): void
    {
        $this->assertSame([], StorageCompletion::paths('missing:foo'));
    }

    public function test_artisan_commands_expose_file_and_disk_completion(): void
    {
        $tester = new CommandCompletionTester(Artisan::all()['storage:cat']);

        $this->assertContains('fixture:reports/hello.txt', $tester->complete(['fixture:reports/h']));
        $this->assertContains('fixture', $tester->complete(['--disk', 'fix']));
    }
}
