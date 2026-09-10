<?php
namespace App\Http\Controllers;
use App\Http\Controllers\Shared\BaseApiController;
use App\Models\TodoItem;
use App\Models\TodoReminder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class TenantTodoController extends BaseApiController
{
 private function owner(): int { return (int) auth()->id(); }
 private function q() { return TodoItem::where('tenant_id',request()->attributes->get('tenant_id'))->where('owner_user_id',$this->owner()); }
 private function item(string $uuid): TodoItem { return $this->q()->with(['owner','reminders'])->where('uuid',$uuid)->firstOrFail(); }
 private function payload(TodoItem $item): array {
  $item->load(['owner','reminders']);
  $data=$item->toArray();
  $data['owner_user']=$item->owner?->only(['uuid','display_name','email']);
  $data['reminders']=$item->reminders->map(fn($r)=>$r->only(['uuid','remind_at','channel','repeat','metadata','status','sent_at']))->values()->all();
  return $data;
 }
 private function fields(Request $r): array {
  return $r->validate(['title'=>'sometimes|required|string|max:255','name'=>'sometimes|string|max:255','description'=>'nullable|string','status'=>'sometimes|in:pending,in_progress,completed,archived','priority'=>'sometimes|in:low,normal,high,urgent','scheduled_at'=>'nullable|date','due_at'=>'nullable|date','completed_at'=>'nullable|date','is_recurring'=>'sometimes|boolean','recurrence_rule'=>'nullable|string|max:255','display_options'=>'nullable|array']);
 }
 public function index(Request $r) {
  $q=$this->q()->with('owner');
  if($r->filled('search')) $q->where(fn($x)=>$x->where('title','like','%'.$r->search.'%')->orWhere('description','like','%'.$r->search.'%'));
  foreach(['status','priority'] as $f) if($r->filled($f)) $q->where($f,$r->input($f));
  if($r->filled('from')) $q->whereDate('scheduled_at','>=',$r->input('from'));
  if($r->filled('to')) $q->whereDate('scheduled_at','<=',$r->input('to'));
  if($r->boolean('overdue')) $q->whereNotNull('due_at')->where('due_at','<',now())->where('status','!=','completed');
  $p=$q->orderByRaw('completed_at is not null')->orderBy('due_at')->paginate((int)$r->integer('per_page',25));
  return $this->list($p->getCollection()->map(fn(TodoItem $i)=>$this->payload($i))->all(),$p,'To-dos fetched.');
 }
 public function store(Request $r) {
  $d=$this->fields($r); unset($d['name']);
  if(isset($d['completed_at']) && !isset($d['status'])) $d['status']='completed';
  $i=TodoItem::create(array_merge($d,['uuid'=>(string)Str::uuid(),'tenant_id'=>request()->attributes->get('tenant_id'),'owner_user_id'=>$this->owner(),'title'=>$d['title']??$r->input('name')]));
  return $this->success(['todo_list'=>$this->payload($i)],'To-do created.',201);
 }
 public function show(string $uuid){return $this->success(['todo_list'=>$this->payload($this->item($uuid))]);}
 public function update(Request $r,string $uuid){$i=$this->item($uuid);$d=$this->fields($r);if(isset($d['name'])&&!isset($d['title']))$d['title']=$d['name'];unset($d['name']);$i->fill($d)->save();return $this->success(['todo_list'=>$this->payload($i->fresh())],'To-do updated.');}
 public function destroy(string $uuid){$this->item($uuid)->delete();return $this->success(null,'To-do archived.');}
 public function dashboard(){
  $q=$this->q(); $upcoming=(clone $q)->whereNotNull('scheduled_at')->where('scheduled_at','>=',now())->where('status','!=','completed')->orderBy('scheduled_at')->limit(10)->get()->map(fn($i)=>$this->payload($i));
  $reminders=TodoReminder::where('tenant_id',request()->attributes->get('tenant_id'))->where('user_id',$this->owner())->where('status','pending')->where('remind_at','>=',now())->orderBy('remind_at')->limit(10)->get();
  return $this->success(['dashboard'=>['counts'=>['active'=>(clone $q)->where('status','!=','archived')->count(),'pending'=>(clone $q)->where('status','pending')->count(),'completed'=>(clone $q)->where('status','completed')->count(),'overdue'=>(clone $q)->whereNotNull('due_at')->where('due_at','<',now())->where('status','!=','completed')->count(),'archived'=>(clone $q)->onlyTrashed()->count()],'upcoming'=>$upcoming,'reminders'=>$reminders,'recent'=>(clone $q)->latest()->limit(10)->get()->map(fn($i)=>$this->payload($i))]]);
 }
 public function kanban(){ $q=$this->q();$groups=[];foreach(['pending','in_progress','completed'] as $status){$rows=(clone $q)->where('status',$status)->orderBy('due_at')->get()->map(fn($i)=>$this->payload($i))->values();$groups[$status]=['total'=>$rows->count(),'rows'=>$rows];}return $this->success(['kanban'=>$groups]); }
 public function calendar(Request $r){$q=$this->q()->whereNotNull('scheduled_at');if($r->filled('from'))$q->whereDate('scheduled_at','>=',$r->input('from'));if($r->filled('to'))$q->whereDate('scheduled_at','<=',$r->input('to'));return $this->success(['tasks'=>$q->orderBy('scheduled_at')->get()->map(fn($i)=>$this->payload($i))]);}
 public function export(Request $r){return $this->success(['status'=>'queued','job_id'=>(string)Str::uuid(),'scope'=>'current_user'],'To-do export queued.',202);}
 public function tasks(string $uuid){$i=$this->item($uuid);return $this->success(['tasks'=>[$this->payload($i)]]);}
 private function remindersQuery(TodoItem $i){return TodoReminder::where('tenant_id',request()->attributes->get('tenant_id'))->where('user_id',$this->owner())->where('todo_item_id',$i->id);}
 public function reminders(string $uuid){return $this->success(['reminders'=>$this->remindersQuery($this->item($uuid))->orderBy('remind_at')->get()]);}
 public function createReminder(Request $r,string $uuid){$i=$this->item($uuid);$d=$r->validate(['remind_at'=>'required|date','channel'=>'sometimes|in:in_app,email,push','repeat'=>'nullable|string|max:100','metadata'=>'nullable|array']);$rem=TodoReminder::create(array_merge($d,['uuid'=>(string)Str::uuid(),'tenant_id'=>request()->attributes->get('tenant_id'),'todo_item_id'=>$i->id,'user_id'=>$this->owner()]));return $this->success(['reminder'=>$rem],'Reminder created.',201);}
 public function updateReminder(Request $r,string $uuid,string $reminderUuid){$rem=$this->remindersQuery($this->item($uuid))->where('uuid',$reminderUuid)->firstOrFail();$d=$r->validate(['remind_at'=>'sometimes|date','channel'=>'sometimes|in:in_app,email,push','repeat'=>'nullable|string|max:100','metadata'=>'nullable|array','status'=>'sometimes|in:pending,sent,cancelled']);$rem->update($d);return $this->success(['reminder'=>$rem->fresh()],'Reminder updated.');}
 public function deleteReminder(string $uuid,string $reminderUuid){$this->remindersQuery($this->item($uuid))->where('uuid',$reminderUuid)->firstOrFail()->delete();return $this->success(null,'Reminder deleted.');}
}
