<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
final class Note extends Model
{
    use SoftDeletes; protected $table='notes'; protected $guarded=['id'];
    public function notable(): \Illuminate\Database\Eloquent\Relations\MorphTo { return $this->morphTo(); }
    public function createdBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(User::class,'created_by'); }
    public function updatedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(User::class,'updated_by'); }
    public function platformCreatedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(PlatformUser::class,'platform_created_by'); }
    public function platformUpdatedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(PlatformUser::class,'platform_updated_by'); }
}
