<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
final class TodoReminder extends Model {
 protected $table='todo_reminders'; protected $guarded=['id']; protected $casts=['metadata'=>'array','remind_at'=>'datetime','sent_at'=>'datetime'];
 public function todo(){return $this->belongsTo(TodoItem::class,'todo_item_id');}
 public function user(){return $this->belongsTo(User::class);}
}
