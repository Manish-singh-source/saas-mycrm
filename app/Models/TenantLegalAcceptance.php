<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class TenantLegalAcceptance extends Model
{
    protected $table = 'tenant_legal_acceptances';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = ['tenant_id' => 'integer', 'legal_document_id' => 'integer', 'user_id' => 'integer', 'accepted_at' => 'datetime'];

    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Tenant::class, 'tenant_id'); }

    public function document(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(LegalDocument::class, 'legal_document_id'); }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
}
