<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class Feature extends Model
{
    protected $table = 'features';

    protected $fillable = ['uuid', 'module', 'name', 'code', 'data_type', 'unit', 'description', 'status'];

    protected static function booted(): void {
        static::creating(function (self $feature): void {
            if (! filled($feature->uuid)) $feature->uuid = (string) Str::uuid();
            if (! filled($feature->code)) $feature->code = self::nextCode();
        });
    }

    private static function nextCode(): string {
        $next = self::query()->get(['code'])->map(fn (self $row): int => (int) preg_replace('/^PL-FEAT-/', '', (string) $row->code))->max() + 1;
        do { $code = sprintf('PL-FEAT-%04d', $next++); } while (self::query()->where('code', $code)->exists());
        return $code;
    }

    public function moduleRelation(): \Illuminate\Database\Eloquent\Relations\BelongsTo {
        return $this->belongsTo(Module::class, 'module', 'code');
    }

    public function plans(): \Illuminate\Database\Eloquent\Relations\BelongsToMany { 
        return $this->belongsToMany(Plan::class, 'plan_features', 'feature_id', 'plan_id')->withPivot(['value', 'metadata']); 
    }
    
    public function planFeatures(): \Illuminate\Database\Eloquent\Relations\HasMany { 
        return $this->hasMany(PlanFeature::class, 'feature_id'); 
    }

}
