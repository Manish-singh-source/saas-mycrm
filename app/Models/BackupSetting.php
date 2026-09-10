<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class BackupSetting extends Model
{
    protected $table = 'backup_settings';

    protected $fillable = ['key', 'value', 'updated_by'];

    protected $casts = ['value' => 'array', 'updated_by' => 'integer'];

    public function updatedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo { 
        return $this->belongsTo(PlatformUser::class, 'updated_by'); 
    }

}
