<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class PlatformAnnouncement extends Model
{
    use SoftDeletes;

    protected $table = 'platform_announcements';

    protected $fillable = ['uuid', 'title', 'body', 'audience', 'status', 'published_at', 'created_by'];

    protected $casts = ['published_at' => 'datetime', 'created_by' => 'integer'];
    
    public function createdBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PlatformUser::class, 'created_by');
    }
}
