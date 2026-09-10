<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class Module extends Model
{
    protected $table = 'modules';

    protected $fillable = ['uuid', 'name', 'code', 'description', 'icon', 'category', 'is_core', 'status', 'sort_order'];

    protected $casts = ['is_core' => 'boolean', 'sort_order' => 'integer'];

    protected static function booted(): void
    {
        static::creating(function (self $module): void {
            if (! filled($module->uuid)) $module->uuid = (string) Str::uuid();
            if (! filled($module->code)) $module->code = self::nextCode();
        });
    }

    private static function nextCode(): string
    {
        $next = self::query()->get(['code'])
            ->map(fn (self $row): int => (int) preg_replace('/^PL-MOD-/', '', (string) $row->code))
            ->max() + 1;
        do { $code = sprintf('PL-MOD-%04d', $next++); } while (self::query()->where('code', $code)->exists());
        return $code;
    }

    public function features(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Feature::class, 'module', 'code');
    }

    public function tenantOverrides(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TenantModuleOverride::class, 'module_code', 'code');
    }

    public function activityLogs(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }
}