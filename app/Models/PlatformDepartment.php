<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
final class PlatformDepartment extends Model
{
    protected $table = 'platform_departments';
    protected $fillable = ['uuid','parent_id','name','code','platform_manager_user_id','status'];
    protected $casts = ['parent_id'=>'integer'];
    protected static function booted(): void
    {
        static::creating(function (self $department): void {
            if (! filled($department->uuid)) $department->uuid = (string) Str::uuid();
            if (! filled($department->code)) $department->code = self::nextCode();
        });
    }
    private static function nextCode(): string
    {
        $next = self::query()->get(['code'])->map(fn (self $row): int => (int) preg_replace('/^PL-DEPT-/', '', (string) $row->code))->max() + 1;
        do { $code = sprintf('PL-DEPT-%04d', $next++); } while (self::query()->where('code',$code)->exists());
        return $code;
    }
    public function parent(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(self::class,'parent_id'); }
    public function children(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(self::class,'parent_id'); }
    public function manager(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(PlatformUser::class,'platform_manager_user_id'); }
    public function users(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(PlatformUser::class,'department_id'); }
    public function teams(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(PlatformTeam::class,'platform_department_id'); }
}