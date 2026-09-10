<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class RemoteLoginSession extends Model
{
    protected $table = 'remote_login_sessions';

    protected $guarded = ['id'];

    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Tenant::class); }
    public function platformUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(PlatformUser::class); }
    public function targetUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(User::class, 'target_user_id'); }
}
