<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class PlatformApiToken extends Model
{
    use SoftDeletes;

    protected $table = 'platform_api_tokens';

    protected $fillable = ['uuid', 'name', 'token_hash', 'encrypted_token_preview', 'abilities', 'last_used_at', 'expires_at', 'created_by'];

    protected $casts = [
        'abilities' => 'array', 'last_used_at' => 'datetime', 'expires_at' => 'datetime', 'created_by' => 'integer',
    ];

    public function createdBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo { 
        return $this->belongsTo(PlatformUser::class, 'created_by'); 
    }

}
