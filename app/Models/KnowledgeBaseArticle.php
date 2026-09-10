<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class KnowledgeBaseArticle extends Model
{
    use SoftDeletes;

    protected $table = 'knowledge_base_articles';

    protected $fillable = ['uuid', 'category_id', 'title', 'slug', 'body', 'audience', 'status', 'created_by', 'published_at'];

    protected $casts = ['category_id' => 'integer', 'created_by' => 'integer', 'published_at' => 'datetime'];

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(KnowledgeBaseCategory::class, 'category_id'); }
    public function createdBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(PlatformUser::class, 'created_by'); }
    public function activityLogs(): \Illuminate\Database\Eloquent\Relations\MorphMany { return $this->morphMany(ActivityLog::class, 'subject'); }
}
