<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class LegalDocument extends Model
{
    use SoftDeletes;

    protected $table = 'legal_documents';

    protected $fillable = ['uuid', 'document_type', 'title', 'version', 'content', 'status', 'published_at', 'created_by'];

    protected $casts = ['published_at' => 'datetime', 'created_by' => 'integer'];

    public function createdBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo { 
        return $this->belongsTo(PlatformUser::class, 'created_by'); 
    }

    public function acceptances(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(TenantLegalAcceptance::class, 'legal_document_id'); }

}
