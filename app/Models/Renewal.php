<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Renewal extends Model
{
    use SoftDeletes;

    protected $table = 'renewals';

    protected $guarded = ['id'];
    public function party(): BelongsTo { return $this->belongsTo(Party::class, 'party_id'); }
    public function owner(): BelongsTo { return $this->belongsTo(User::class, 'owner_user_id'); }
    public function items(): Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(RenewalItem::class); }
    public function reminders(): Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(RenewalReminder::class); }
}
