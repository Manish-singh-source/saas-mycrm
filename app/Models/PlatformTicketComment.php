<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PlatformTicketComment extends Model
{
    protected $table = 'platform_ticket_comments';

    protected $guarded = ['id'];

    public function ticket(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PlatformTicket::class, 'platform_ticket_id');
    }

    public function platformUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PlatformUser::class, 'platform_user_id');
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
