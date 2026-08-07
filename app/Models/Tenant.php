<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'organization_name',
        'legal_name',
        'display_name',
        'organization_code',
        'slug',
        'business_type_id',
        'industry_id',
        'company_size',
        'gst_number',
        'pan_number',
        'registration_number',
        'website',
        'logo_file_id',
        'favicon_file_id',
        'default_currency',
        'default_timezone',
        'onboarded_at',
        'trial_ends_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'onboarded_at' => 'datetime',
            'trial_ends_at' => 'datetime',
        ];
    }
}