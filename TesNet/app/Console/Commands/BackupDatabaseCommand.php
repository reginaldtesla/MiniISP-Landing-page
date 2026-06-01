<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BackupDatabaseCommand extends Command
{
    protected $signature = 'tesnet:backup-database';

    protected $description = 'Dump the application database to storage/backups';

    public function handle(): int
    {
        if (! config('tesnet.backup.enabled', true)) {
            $this->warn('Backups disabled (TESNET_BACKUP_ENABLED=false).');

            return self::SUCCESS;
        }

        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        if (! in_array($config['driver'] ?? '', ['mysql', 'mariadb'], true)) {
            $this->error('Backup supports mysql/mariadb only.');

            return self::FAILURE;
        }

        $dir = config('tesnet.backup.path', storage_path('backups'));
        File::ensureDirectoryExists($dir);

        $filename = sprintf(
            'tesnet_%s_%s.sql.gz',
            $config['database'],
            now()->format('Y-m-d_His')
        );
        $path = $dir.DIRECTORY_SEPARATOR.$filename;

        $host = escapeshellarg((string) $config['host']);
        $port = escapeshellarg((string) ($config['port'] ?? '3306'));
        $user = escapeshellarg((string) $config['username']);
        $db = escapeshellarg((string) $config['database']);
        $password = (string) ($config['password'] ?? '');

        $dumpBin = $this->findExecutable(['mysqldump', 'mariadb-dump']);
        if ($dumpBin === null) {
            $this->error('mysqldump not found in PATH. Install MariaDB client tools.');

            return self::FAILURE;
        }

        $gzipBin = $this->findExecutable(['gzip']);
        if ($gzipBin === null) {
            $this->error('gzip not found in PATH.');

            return self::FAILURE;
        }

        $passArg = $password !== '' ? '-p'.escapeshellarg($password) : '';

        $command = sprintf(
            '%s -h %s -P %s -u %s %s %s --single-transaction --quick 2>&1 | %s > %s',
            escapeshellarg($dumpBin),
            $host,
            $port,
            $user,
            $passArg,
            $db,
            escapeshellarg($gzipBin),
            escapeshellarg($path)
        );

        if (PHP_OS_FAMILY === 'Windows') {
            $command = 'cmd /C '.$command;
        }

        exec($command, $output, $code);

        if ($code !== 0 || ! is_file($path) || filesize($path) < 32) {
            $this->error('Backup failed.');
            if ($output !== []) {
                $this->line(implode("\n", $output));
            }
            @unlink($path);

            return self::FAILURE;
        }

        $this->info('Backup saved: '.$path);
        $this->pruneOldBackups($dir);

        return self::SUCCESS;
    }

    protected function pruneOldBackups(string $dir): void
    {
        $days = (int) config('tesnet.backup.retain_days', 14);
        $cutoff = now()->subDays($days)->getTimestamp();

        foreach (glob($dir.DIRECTORY_SEPARATOR.'tesnet_*.sql.gz') ?: [] as $file) {
            if (is_file($file) && filemtime($file) < $cutoff) {
                @unlink($file);
                $this->line('Pruned old backup: '.basename($file));
            }
        }
    }

    /**
     * @param  list<string>  $names
     */
    protected function findExecutable(array $names): ?string
    {
        foreach ($names as $name) {
            $which = PHP_OS_FAMILY === 'Windows' ? "where {$name}" : "which {$name}";
            exec($which, $out, $code);
            if ($code === 0 && isset($out[0])) {
                return trim($out[0]);
            }
        }

        return null;
    }
}
