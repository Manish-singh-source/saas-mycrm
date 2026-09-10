<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Party extends Model
{
    use SoftDeletes;

    protected $table = 'parties';

    protected $guarded = ['id'];
    public function clientProfile(): HasOne { return $this->hasOne(ClientProfile::class); }
    public function vendorProfile(): HasOne { return $this->hasOne(VendorProfile::class); }
    public function contacts(): HasMany { return $this->hasMany(PartyContact::class); }
    public function addresses(): HasMany { return $this->hasMany(PartyAddress::class); }
    public function renewals(): HasMany { return $this->hasMany(Renewal::class, 'party_id'); }
    public function owner(): BelongsTo { return $this->belongsTo(User::class, 'owner_user_id'); }
}
