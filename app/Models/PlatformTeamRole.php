<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

final class PlatformTeamRole extends Model
{
    use SoftDeletes;

    protected $table = 'platform_team_roles';

    protected $fillable = ['name', 'code', 'description', 'permissions', 'sort_order', 'is_system', 'status'];

    protected static function booted(): void
    {
        static::creating(function (self $role): void {
            if (! $role->uuid) $role->uuid = (string) Str::uuid();
            if (! filled($role->code)) {
                $next = self::withTrashed()->where('code', 'like', 'PL-TEAM-ROLE-%')->get(['code'])->map(fn (self $record): int => (int) preg_replace('/^PL-TEAM-ROLE-/', '', (string) $record->code))->max() + 1;
                do {
                    $code = sprintf('PL-TEAM-ROLE-%04d', $next++);
                } while (self::withTrashed()->where('code', $code)->exists());
                $role->code = $code;
            }
        });
    }

    protected $casts = [
        'sort_order' => 'integer', 'permissions' => 'array',
        'is_system' => 'boolean',
    ];
    
    public function members(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PlatformTeamMember::class, 'platform_team_role_id');
    }
}
