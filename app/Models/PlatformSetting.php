<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PlatformSetting extends Model
{
    protected $table = 'platform_settings';

    protected $fillable = ['group', 'key', 'value', 'value_type', 'is_encrypted', 'updated_by'];

    protected $casts = ['value' => 'array', 'is_encrypted' => 'boolean', 'updated_by' => 'integer'];
    
    public function updatedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PlatformUser::class, 'updated_by');
    }
}
