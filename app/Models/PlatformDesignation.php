<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
final class PlatformDesignation extends Model
{
    protected $table='platform_designations';
    protected $fillable=['uuid','name','code','description','level','status'];
    protected $casts=['level'=>'integer'];
    protected static function booted(): void
    {
        static::creating(function (self $designation): void {
            if (! filled($designation->uuid)) $designation->uuid = (string) Str::uuid();
            if (! filled($designation->code)) $designation->code = self::nextCode();
        });
    }
    private static function nextCode(): string
    {
        $next = self::query()->get(['code'])->map(fn (self $row): int => (int) preg_replace('/^PL-DES-/', '', (string) $row->code))->max() + 1;
        do { $code = sprintf('PL-DES-%04d', $next++); } while (self::query()->where('code',$code)->exists());
        return $code;
    }
    public function users(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(PlatformUser::class,'designation_id'); }
}