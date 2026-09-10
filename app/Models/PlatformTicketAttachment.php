<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PlatformTicketAttachment extends Model
{
    protected $table = 'platform_ticket_attachments';

    protected $guarded = ['id'];

    public function ticket(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PlatformTicket::class, 'platform_ticket_id');
    }

    public function file(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(File::class, 'file_id');
    }

    public function createdBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PlatformUser::class, 'created_by');
    }
}
