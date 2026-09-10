<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class TenantApiToken extends Model
{
    use SoftDeletes;

    protected $table = 'tenant_api_tokens';

    protected $guarded = ['id'];
    protected $casts = ['abilities' => 'array', 'last_used_at' => 'datetime', 'expires_at' => 'datetime'];
}
