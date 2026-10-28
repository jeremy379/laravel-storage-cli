# Laravel Storage CLI

Read-only Artisan commands for exploring Laravel filesystem disks (local, S3, FTP, and other Flysystem adapters). List files, inspect metadata, print content, generate URLs, and download files without opening Tinker.

## Requirements

- PHP 8.2+
- Laravel 11, 12, or 13

## Installation

The package is available on [Packagist](https://packagist.org/packages/jeremy379/laravel-storage-cli). Until a stable version is tagged, install the development branch:

```bash
composer require jeremy379/laravel-storage-cli:dev-main
```

Once a stable release is tagged, installation will simply be `composer require jeremy379/laravel-storage-cli`.

Laravel discovers the service provider automatically. Use `--dev` with Composer if these commands are only needed in development.

## Usage

```bash
# Show configured disks
php artisan storage:ls

# List a directory, including remote S3 disks
php artisan storage:ls s3:invoices/2026
php artisan storage:ls --disk=s3 invoices/2026

# Recursive, detailed, and JSON output
php artisan storage:ls s3:invoices --recursive --long
php artisan storage:ls s3:invoices --json

# Print a small text file (1 MiB limit by default)
php artisan storage:cat s3:logs/latest.log
php artisan storage:cat s3:logs/latest.log --max-bytes=5000000

# Generate a public URL or a temporary URL
php artisan storage:url s3:invoices/123.pdf
php artisan storage:url s3:invoices/123.pdf --expires=15

# Download to the machine running Artisan
php artisan storage:get s3:invoices/123.pdf ./invoice.pdf
php artisan storage:get s3:invoices/123.pdf ./invoice.pdf --force

# Inspect metadata
php artisan storage:stat s3:invoices/123.pdf
php artisan storage:stat s3:invoices/123.pdf --json
```

Use `disk:path` or `--disk=name` with a relative path. Without either, the configured default disk is used (except `storage:ls` without arguments, which lists the available disks).

## Shell autocompletion

Enable Symfony Console's shell completion for your Laravel application (Bash, Zsh, or Fish). Run `php artisan completion --help` for shell-specific installation instructions.

Once configured, pressing Tab suggests:

- Configured disk names after `--disk=` (for example, `--disk=s3`).
- Disk prefixes in the location argument (for example, `s3:`).
- Files and folders within the selected disk, including remote disks such as S3. Folder suggestions end with `/`.

Examples:

```bash
php artisan storage:ls s3:inv<TAB>
php artisan storage:cat --disk=s3 reports/hel<TAB>
php artisan storage:get s3:invoices/2026/<TAB>
```

Directory suggestions are non-recursive and limited to 50 results from at most 500 scanned entries. Completing a remote disk may involve a network request and its associated latency or API charges. Unavailable disks do not interrupt shell completion.

## Behavior and safety

- All commands are **read-only on the Laravel disks**. `storage:get` writes only to the selected local destination.
- `storage:get` streams a file to a temporary local file and moves it into place only after transfer succeeds. Existing destinations require `--force`.
- The download destination is on **the machine running Artisan**. When you run the command over SSH, it saves the file on the server.
- `storage:cat` writes raw bytes to stdout: avoid printing binary files to a terminal. The default 1 MiB limit can be raised with `--max-bytes` or bypassed using `--force`.
- `storage:url` without `--expires` uses Laravel's configured `url()` implementation. It does **not** make private files public. The `--expires` option requires a disk supporting temporary URLs.
- Signed URLs may grant access until they expire. Handle them as sensitive.
- Recursive listings can be expensive on large cloud buckets. Prefer a narrow directory.
- Paths containing a `..` segment are rejected.
- Disk credentials and configuration come from the Laravel application. No extra configuration is needed.

## Development

```bash
composer install
composer test
```

The tests use Orchestra Testbench. GitHub Actions tests Laravel 12 and 13. Laravel 11 remains allowed for existing projects, but its available releases are currently blocked by Composer security advisories, so CI does not install it.

## License

MIT. See [LICENSE](LICENSE).
