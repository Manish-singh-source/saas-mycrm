<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
final class Attachment extends Model
{
    protected $table='attachments'; public $timestamps=false; protected $guarded=['id'];
    public function file(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(File::class,'file_id'); }
    public function attachable(): \Illuminate\Database\Eloquent\Relations\MorphTo { return $this->morphTo(); }
    public function createdBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(User::class,'created_by'); }
}
