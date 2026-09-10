<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
final class File extends Model
{
    use SoftDeletes;
    protected $table='files'; protected $guarded=['id'];
    public function platformInvoicePdfs(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(PlatformInvoice::class,'pdf_file_id'); }
    public function platformTicketAttachments(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(PlatformTicketAttachment::class,'file_id'); }
    public function attachments(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(Attachment::class,'file_id'); }
    public function getUrlAttribute(): ?string
    {
        if (! $this->path || ! $this->disk) return null;
        $disk = \Illuminate\Support\Facades\Storage::disk($this->disk);
        try {
            $url = $this->visibility === 'public' ? $disk->url($this->path) : $disk->temporaryUrl($this->path, now()->addMinutes(10));
        } catch (\Throwable) {
            $url = $disk->url($this->path);
        }
        if (app()->bound('request')) {
            $parts = parse_url($url);
            if (is_array($parts) && isset($parts['path'])) $url = request()->getSchemeAndHttpHost().$parts['path'].(isset($parts['query']) ? '?'.$parts['query'] : '');
        }
        return $url;
    }    public function uploader(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(User::class,'uploaded_by'); }
    public function platformUploader(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(PlatformUser::class,'platform_uploaded_by'); }
}
