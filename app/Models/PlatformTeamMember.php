<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PlatformTeamMember extends Model
{
    protected $table = 'platform_team_members';

    protected $fillable = ['platform_team_id', 'platform_user_id', 'platform_team_role_id', 'joined_at', 'left_at', 'status'];

    protected $casts = ['platform_team_id' => 'integer', 'platform_user_id' => 'integer', 'platform_team_role_id' => 'integer', 'joined_at' => 'date', 'left_at' => 'date',];

    public function team(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PlatformTeam::class, 'platform_team_id');
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PlatformUser::class, 'platform_user_id');
    }

    public function teamRole(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PlatformTeamRole::class, 'platform_team_role_id');
    }
}
