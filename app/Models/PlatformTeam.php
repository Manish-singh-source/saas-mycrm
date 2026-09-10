<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

final class PlatformTeam extends Model
{
    use SoftDeletes;

    protected $table = 'platform_teams';

    protected $fillable = ['platform_department_id', 'name', 'code', 'description', 'lead_platform_user_id', 'assistant_lead_platform_user_id', 'email', 'phone', 'color', 'icon', 'visibility', 'status'];

    protected static function booted(): void
    {
        static::creating(function (self $team): void {
            if (! $team->uuid) $team->uuid = (string) Str::uuid();
            if (! filled($team->code)) {
                $next = self::withTrashed()->where('code', 'like', 'PL-TEAM-%')->get(['code'])->map(fn (self $record): int => (int) preg_replace('/^PL-TEAM-/', '', (string) $record->code))->max() + 1;
                do {
                    $code = sprintf('PL-TEAM-%04d', $next++);
                } while (self::withTrashed()->where('code', $code)->exists());
                $team->code = $code;
            }
        });
    }
    
    public function department(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PlatformDepartment::class, 'platform_department_id');
    }

    public function lead(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PlatformUser::class, 'lead_platform_user_id');
    }

    public function assistantLead(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PlatformUser::class, 'assistant_lead_platform_user_id');
    }

    public function members(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PlatformTeamMember::class, 'platform_team_id');
    }

    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(PlatformUser::class, 'platform_team_members', 'platform_team_id', 'platform_user_id')->withPivot(['platform_team_role_id', 'joined_at', 'left_at', 'status']);
    }

    public function assignments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PlatformTeamAssignment::class, 'platform_team_id');
    }

}
