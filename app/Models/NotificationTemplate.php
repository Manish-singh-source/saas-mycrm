<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class NotificationTemplate extends Model
{
    protected $table = 'notification_templates';

    protected $fillable = ['uuid', 'tenant_id', 'code', 'channel', 'subject', 'body', 'variables', 'status'];

    protected $casts = ['variables' => 'array', 'tenant_id' => 'integer'];
}
