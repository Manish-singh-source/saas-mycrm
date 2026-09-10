<?php

namespace App\Providers;

use App\Models\SchedulerLog;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use App\Models\QueueJobLog;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Event::listen(JobProcessing::class, function (JobProcessing $event): void {
            QueueJobLog::query()->create([
                'queue' => $event->job->getQueue(),
                'job_name' => $event->job->resolveName(),
                'status' => 'running',
                'attempts' => $event->job->attempts(),
                'started_at' => now(),
            ]);
        });

        Event::listen(JobProcessed::class, function (JobProcessed $event): void {
            $log = QueueJobLog::query()->where('queue', $event->job->getQueue())->where('job_name', $event->job->resolveName())->where('status', 'running')->latest('id')->first();
            $log?->forceFill(['status' => 'succeeded', 'finished_at' => now()])->save();
        });

        Event::listen(JobExceptionOccurred::class, function (JobExceptionOccurred $event): void {
            $log = QueueJobLog::query()->where('queue', $event->job->getQueue())->where('job_name', $event->job->resolveName())->where('status', 'running')->latest('id')->first();
            $log?->forceFill(['status' => 'failed', 'exception' => $event->exception->getMessage(), 'finished_at' => now()])->save();
        });
        Event::listen(ScheduledTaskStarting::class, function (ScheduledTaskStarting $event): void {
            SchedulerLog::query()->create(['command' => $event->task->command ?: $event->task->getSummaryForDisplay(), 'status' => 'running', 'started_at' => now()]);
        });

        Event::listen(ScheduledTaskFinished::class, function (ScheduledTaskFinished $event): void {
            $command = $event->task->command ?: $event->task->getSummaryForDisplay();
            $log = SchedulerLog::query()->where('command', $command)->where('status', 'running')->latest('id')->first();
            $log?->forceFill(['status' => 'succeeded', 'output' => 'Completed in '.round($event->runtime * 1000).' ms.', 'finished_at' => now()])->save();
        });

        Event::listen(ScheduledTaskFailed::class, function (ScheduledTaskFailed $event): void {
            $command = $event->task->command ?: $event->task->getSummaryForDisplay();
            $log = SchedulerLog::query()->where('command', $command)->where('status', 'running')->latest('id')->first();
            $log?->forceFill(['status' => 'failed', 'output' => $event->exception->getMessage(), 'finished_at' => now()])->save();
        });

        RateLimiter::for('api-common', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));
        RateLimiter::for('api-health', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));
        RateLimiter::for('api-auth', fn (Request $request) => Limit::perMinute(10)->by($request->ip().'|'.strtolower((string) $request->input('email'))));
        RateLimiter::for('api-password-forgot', fn (Request $request) => Limit::perMinute(5)->by($request->ip().'|'.strtolower((string) $request->input('email'))));
        RateLimiter::for('api-password-reset', fn (Request $request) => Limit::perMinute(10)->by($request->ip().'|'.strtolower((string) $request->input('email'))));
        RateLimiter::for('api-public-security', fn (Request $request) => Limit::perMinute(10)->by($request->ip().'|'.strtolower((string) $request->input('email'))));
        RateLimiter::for('api-preferences-read', fn (Request $request) => Limit::perMinute(60)->by($request->ip().'|'.strtolower((string) $request->input('email'))));
        RateLimiter::for('api-preferences-write', fn (Request $request) => Limit::perMinute(30)->by($request->ip().'|'.strtolower((string) $request->input('email'))));
        RateLimiter::for('api-authenticated', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->getAuthIdentifier() ? 'user:'.$request->user()->getAuthIdentifier() : $request->ip()));
    }
}