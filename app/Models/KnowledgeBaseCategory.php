<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class KnowledgeBaseCategory extends Model
{
    protected $table = 'knowledge_base_categories';

    protected $fillable = ['uuid', 'parent_id', 'name', 'slug', 'audience', 'status'];

    protected $casts = ['parent_id' => 'integer'];

    public function parent(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(self::class, 'parent_id'); }
    public function children(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(self::class, 'parent_id'); }
    public function articles(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(KnowledgeBaseArticle::class, 'category_id'); }
    public function activityLogs(): \Illuminate\Database\Eloquent\Relations\MorphMany { return $this->morphMany(ActivityLog::class, 'subject'); }
}
