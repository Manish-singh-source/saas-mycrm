<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class TenantOffice extends Model
{
    use SoftDeletes;

    protected $table = 'tenant_offices';

    protected $guarded = ['id'];

    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Tenant::class); }
    public function country(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Country::class); }
    public function state(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(State::class); }
    public function city(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(City::class); }
}
