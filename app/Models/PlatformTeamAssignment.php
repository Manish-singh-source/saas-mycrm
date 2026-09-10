<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PlatformTeamAssignment extends Model
{
    protected $table = 'platform_team_assignments';

    protected $fillable = [];

    public function team(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PlatformTeam::class, 'platform_team_id');
    }

    public function assignedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PlatformUser::class, 'assigned_by');
    }
    
    public function assignable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }
}
