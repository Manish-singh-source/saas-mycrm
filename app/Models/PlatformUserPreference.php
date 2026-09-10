<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PlatformUserPreference extends Model
{
    protected $table = 'platform_user_preferences';

    protected $fillable = ['group', 'key', 'value'];

    protected $casts = ['platform_user_id' => 'integer', 'value' => 'array'];
    
    public function platformUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PlatformUser::class, 'platform_user_id');
    }
}
