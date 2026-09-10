<?php

namespace App\Jobs;

use App\Models\BackupRun;
use App\Models\File;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Throwable;

final class ProcessPlatformBackup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;

    public function __construct(public int $backupRunId) {}

    public function handle(): void
    {
        $run = BackupRun::query()->findOrFail($this->backupRunId);
        $run->forceFill(['status' => 'running', 'started_at' => now(), 'error_message' => null])->save();

        try {
            if (! in_array($run->backup_type, ['full', 'database'], true)) {
                throw new \RuntimeException('Only full and database backups are currently supported.');
            }

            $dump = $this->databaseDump();
            $disk = (string) config('filesystems.default', 'local');
            $path = 'backups/platform-'.$run->uuid.'.sql';
            if (! Storage::disk($disk)->put($path, $dump)) {
                throw new \RuntimeException('The backup file could not be stored.');
            }

            $file = File::query()->create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => null,
                'platform_uploaded_by' => null,
                'disk' => $disk,
                'path' => $path,
                'original_name' => 'platform-'.$run->uuid.'.sql',
                'mime_type' => 'application/sql',
                'extension' => 'sql',
                'size_bytes' => strlen($dump),
                'checksum' => hash('sha256', $dump),
                'visibility' => 'private',
            ]);

            $run->forceFill(['status' => 'completed', 'file_id' => $file->id, 'finished_at' => now()])->save();
        } catch (Throwable $exception) {
            $run->forceFill(['status' => 'failed', 'finished_at' => now(), 'error_message' => $exception->getMessage()])->save();
            throw $exception;
        }
    }

    private function databaseDump(): string
    {
        $connection = config('database.default');
        if (! in_array($connection, ['mysql', 'mariadb'], true)) {
            throw new \RuntimeException('Platform database backups currently require a MySQL or MariaDB connection.');
        }

        $binary = (string) env('MYSQLDUMP_PATH', 'mysqldump');
        if (PHP_OS_FAMILY === 'Windows' && $binary === 'mysqldump') {
            $xamppBinary = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
            if (is_file($xamppBinary)) $binary = $xamppBinary;
        }

        $config = config('database.connections.'.$connection);
        $process = new Process([
            $binary,
            '--host='.(string) ($config['host'] ?? '127.0.0.1'),
            '--port='.(string) ($config['port'] ?? 3306),
            '--user='.(string) ($config['username'] ?? 'root'),
            '--single-transaction',
            '--routines',
            '--triggers',
            (string) ($config['database'] ?? ''),
        ], base_path());
        $process->setEnv(['MYSQL_PWD' => (string) ($config['password'] ?? '')]);
        $process->setTimeout($this->timeout);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException(trim($process->getErrorOutput()) ?: 'Database dump failed.');
        }

        return $process->getOutput();
    }
}
