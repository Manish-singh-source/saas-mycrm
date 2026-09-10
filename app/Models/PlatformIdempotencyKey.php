<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PlatformIdempotencyKey extends Model
{
    protected $table = 'platform_idempotency_keys';

    protected $fillable = [];

    protected $casts = ['platform_user_id' => 'integer', 'response_status' => 'integer', 'response_body' => 'array'];
    
    public function platformUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PlatformUser::class, 'platform_user_id');
    }
}
