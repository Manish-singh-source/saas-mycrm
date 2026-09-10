<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
final class TodoItem extends Model {
 use SoftDeletes; protected $table='todo_items'; protected $guarded=['id']; protected $casts=['is_recurring'=>'boolean','display_options'=>'array','completed_at'=>'datetime','scheduled_at'=>'datetime','due_at'=>'datetime'];
 public function owner(){return $this->belongsTo(User::class,'owner_user_id');}
 public function reminders(){return $this->hasMany(TodoReminder::class,'todo_item_id');}
}
