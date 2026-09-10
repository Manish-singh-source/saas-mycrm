<?php

namespace App\Console\Commands;

use App\Models\MonitoringService;
use App\Models\MonitoringServiceLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

final class CheckMonitoringServices extends Command
{
    protected $signature = 'monitoring:check-services';

    protected $description = 'Run due health checks for configured monitoring services';

    public function handle(): int
    {
        $now = now();
        $checked = 0;
        $skipped = 0;

        MonitoringService::query()->where('status', 'active')->each(function (MonitoringService $service) use ($now, &$checked, &$skipped): void {
            $latest = MonitoringServiceLog::query()->where('service_id', $service->id)->latest('checked_at')->first();
            $interval = max(1, (int) $service->check_interval_seconds);
            if ($latest?->checked_at && $latest->checked_at->gt($now->copy()->subSeconds($interval))) {
                $skipped++;
                return;
            }

            $startedAt = microtime(true);
            $status = 'healthy';
            $message = null;
            try {
                $message = $this->check($service->service_type);
            } catch (Throwable $exception) {
                $status = 'unhealthy';
                $message = $exception->getMessage();
            }

            MonitoringServiceLog::query()->create([
                'service_id' => $service->id,
                'status' => $status,
                'response_time_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'message' => $message,
                'checked_at' => $now,
            ]);
            $checked++;
        });

        $this->info("Monitoring checks completed: {$checked} checked, {$skipped} skipped.");
        return self::SUCCESS;
    }

    private function check(string $serviceType): string
    {
        return match ($serviceType) {
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
            'storage' => $this->checkStorage(),
            'mail' => $this->checkMail(),
            'scheduler' => 'Scheduler command is running.',
            default => "No health probe configured for {$serviceType}.",
        };
    }

    private function checkDatabase(): string
    {
        DB::select('select 1');
        return 'Database connection is healthy.';
    }

    private function checkCache(): string
    {
        $key = 'monitoring.health.'.Str::uuid();
        Cache::put($key, 'ok', now()->addMinute());
        if (Cache::get($key) !== 'ok') throw new \RuntimeException('Cache read-after-write check failed.');
        Cache::forget($key);
        return 'Cache read/write is healthy.';
    }

    private function checkQueue(): string
    {
        $connection = (string) config('queue.default');
        if ($connection === 'database') {
            $table = (string) config('queue.connections.database.table', 'jobs');
            if (! Schema::hasTable($table)) throw new \RuntimeException("Queue table {$table} does not exist.");
            DB::table($table)->count();
        }
        return "Queue connection {$connection} is configured.";
    }

    private function checkStorage(): string
    {
        $disk = (string) config('filesystems.default');
        $path = 'monitoring-health/'.Str::uuid().'.txt';
        $filesystem = Storage::disk($disk);
        $filesystem->put($path, 'ok');
        if (! $filesystem->exists($path)) throw new \RuntimeException('Storage read-after-write check failed.');
        $filesystem->delete($path);
        return "Storage disk {$disk} is healthy.";
    }

    private function checkMail(): string
    {
        $mailer = (string) config('mail.default');
        if (! config("mail.mailers.{$mailer}")) throw new \RuntimeException("Mail transport {$mailer} is not configured.");
        return "Mail transport {$mailer} is configured.";
    }
}