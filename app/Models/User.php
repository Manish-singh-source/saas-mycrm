<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function staff(): BelongsTo { return $this->belongsTo(Staff::class, 'staff_id'); }
    public function managedStaff(): HasMany { return $this->hasMany(Staff::class, 'reporting_manager_id'); }
    public function defaultOffice(): BelongsTo { return $this->belongsTo(TenantOffice::class, 'default_office_id'); }
    public function roles(): MorphToMany { return $this->morphToMany(Role::class, 'model', 'model_has_roles')->withPivot('tenant_id'); }
    public function hasTenantPermission(string $permission): bool { return $this->roles()->where('roles.tenant_id', $this->tenant_id)->where('roles.status','active')->whereHas('permissions', fn ($q) => $q->where('permissions.name', $permission)->where('permissions.status','active'))->exists(); }
}



