<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['tenant_id', 'actor_user_id', 'actor_platform_user_id', 'subject_type', 'subject_id', 'event', 'description', 'old_values', 'new_values', 'ip_address', 'user_agent', 'request_id', 'created_at'];

    protected function casts(): array
    {
        return ['old_values' => 'array', 'new_values' => 'array', 'created_at' => 'datetime'];
    }
}
