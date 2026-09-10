<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Tenant extends Model
{
    use SoftDeletes;

    protected $table = 'tenants';

    protected $guarded = ['id'];

    public function owner(): \Illuminate\Database\Eloquent\Relations\HasOne { return $this->hasOne(User::class)->where('account_type', 'owner')->whereNull('deleted_at'); }
    public function users(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(User::class)->whereNull('deleted_at'); }
    public function offices(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(TenantOffice::class); }
    public function headOffice(): \Illuminate\Database\Eloquent\Relations\HasOne { return $this->hasOne(TenantOffice::class)->where('is_head_office', true); }
    public function subscription(): \Illuminate\Database\Eloquent\Relations\HasOne { return $this->hasOne(Subscription::class)->ofMany(['id' => 'max'], fn ($q) => $q->whereNull('deleted_at')); }    public function onboardingSteps(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(TenantOnboardingStep::class, 'tenant_id'); }

    public function businessType(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(BusinessType::class); }
    public function industry(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Industry::class); }
    public function logoFile(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(File::class, 'logo_file_id'); }
    public function faviconFile(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(File::class, 'favicon_file_id'); }

    public function platformInvoices(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PlatformInvoice::class, 'tenant_id');
    }

    public function platformPayments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PlatformPayment::class, 'tenant_id');
    }

    public function platformRefunds(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PlatformRefund::class, 'tenant_id');
    }

    public function platformTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PlatformTicket::class, 'tenant_id');
    }


    public function moduleOverrides(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TenantModuleOverride::class, 'tenant_id');
    }
    public function platformWebhookEndpoints(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PlatformWebhookEndpoint::class, 'tenant_id');
    }
}
