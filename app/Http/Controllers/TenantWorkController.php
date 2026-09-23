<?php
namespace App\Http\Controllers;
use App\Http\Controllers\Shared\BaseApiController;
use App\Models\Project;
use App\Models\Task;
use App\Models\ClientIssue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class TenantWorkController extends BaseApiController
{
 private function tid():int{return (int)request()->attributes->get('tenant_id');}
 private function rid(string $table,$uuid):?int{return $uuid?DB::table($table)->where('tenant_id',$this->tid())->where('uuid',$uuid)->value('id'):null;}
 private function lookup($uuid):?int{return $this->rid('tenant_lookups',$uuid);}
 private function project(string $uuid):Project{return Project::where('tenant_id',$this->tid())->where('uuid',$uuid)->firstOrFail();}
 private function task(string $uuid):Task{return Task::where('tenant_id',$this->tid())->where('uuid',$uuid)->firstOrFail();}
 private function issue(string $uuid):ClientIssue{return ClientIssue::where('tenant_id',$this->tid())->where('uuid',$uuid)->firstOrFail();}
 private function rel($table,$id,$fields){return $id?DB::table($table)->where('tenant_id',$this->tid())->where('id',$id)->first($fields):null;}
 private function pp(Project $p):array{$d=$p->toArray();$d['client']=$this->rel('parties',$p->client_party_id,['uuid','display_name','email']);$d['manager']=$this->rel('users',$p->project_manager_id,['uuid','display_name','email']);$d['summary']=['members'=>DB::table('project_members')->where('tenant_id',$this->tid())->where('project_id',$p->id)->count(),'milestones'=>DB::table('project_milestones')->where('tenant_id',$this->tid())->where('project_id',$p->id)->count(),'tasks'=>Task::where('tenant_id',$this->tid())->where('project_id',$p->id)->count()];return $d;}
 private function tp(Task $t):array{$d=$t->toArray();$d['project']=$this->rel('projects',$t->project_id,['uuid','project_number','name','progress']);$d['assignee']=$this->rel('users',$t->assigned_to,['uuid','display_name','email']);$d['team']=$this->rel('teams',$t->assigned_team_id,['uuid','name']);$d['issue']=$t->related_type==='client_issue'?$this->rel('client_issues',$t->related_id,['uuid','issue_number','title']):null;return $d;}
 private function ip(ClientIssue $i):array{$d=$i->toArray();$d['client']=$this->rel('parties',$i->client_party_id,['uuid','display_name','email']);$d['project']=$this->rel('projects',$i->project_id,['uuid','project_number','name']);$d['assignee']=$this->rel('users',$i->assigned_to,['uuid','display_name','email']);$d['team']=$this->rel('teams',$i->assigned_team_id,['uuid','name']);$d['linked_tasks']=Task::where('tenant_id',$this->tid())->where('related_type','client_issue')->where('related_id',$i->id)->get()->map(fn($t)=>$this->tp($t));return $d;}
 private function page($q,Request $r,$map,string $msg){$p=$q->paginate((int)$r->integer('per_page',25));return $this->list($p->getCollection()->map($map)->all(),$p,$msg);}
 public function projects(Request $r){$q=Project::where('tenant_id',$this->tid())->withTrashed(false);if($r->filled('search'))$q->where(fn($x)=>$x->where('name','like','%'.$r->search.'%')->orWhere('project_number','like','%'.$r->search.'%'));if($r->filled('client'))if($id=$this->rid('parties',$r->client))$q->where('client_party_id',$id);if($r->boolean('overdue'))$q->whereDate('due_date','<',now())->whereNull('completed_at');return $this->page($q->orderBy('name'),$r,fn($x)=>$this->pp($x),'Projects fetched.');}
 public function projectDashboard(){ $q=Project::where('tenant_id',$this->tid())->withTrashed(false);return $this->success(['dashboard'=>['active'=>(clone $q)->whereNull('completed_at')->count(),'completed'=>(clone $q)->whereNotNull('completed_at')->count(),'overdue'=>(clone $q)->whereDate('due_date','<',now())->whereNull('completed_at')->count(),'total_budget'=>(clone $q)->sum('budget_amount'),'projects'=>(clone $q)->latest()->limit(10)->get()->map(fn($x)=>$this->pp($x))]]);}
 public function projectKanban(){return $this->success(['view'=>'kanban','columns'=>Project::where('tenant_id',$this->tid())->withTrashed(false)->get()->groupBy('status_id')->map(fn($x)=>$x->map(fn($p)=>$this->pp($p))->values())]);}
 public function projectCalendar(){return $this->success(['projects'=>Project::where('tenant_id',$this->tid())->withTrashed(false)->whereNotNull('due_date')->orderBy('due_date')->get()->map(fn($x)=>$this->pp($x))]);}
 public function projectGantt(){return $this->success(['projects'=>Project::where('tenant_id',$this->tid())->withTrashed(false)->get()->map(fn($p)=>['project'=>$this->pp($p),'milestones'=>DB::table('project_milestones')->where('tenant_id',$this->tid())->where('project_id',$p->id)->get()])]);}
 public function projectShow(string $uuid){$p=$this->project($uuid);$d=$this->pp($p);foreach(['members'=>'project_members','phases'=>'project_phases','milestones'=>'project_milestones','time_logs'=>'project_time_logs','expenses'=>'project_expenses'] as $k=>$t)$d[$k]=DB::table($t)->where('tenant_id',$this->tid())->where('project_id',$p->id)->get();$d['tasks']=Task::where('tenant_id',$this->tid())->where('project_id',$p->id)->get()->map(fn($x)=>$this->tp($x));$d['issues']=ClientIssue::where('tenant_id',$this->tid())->where('project_id',$p->id)->get()->map(fn($x)=>$this->ip($x));return $this->success(['project'=>$d]);}
 private function projectData(Request $r):array{$d=$r->validate(['project_number'=>'required|string|max:80','name'=>'required|string','description'=>'nullable|string','client_party_id'=>'nullable|string','project_manager_id'=>'nullable|string','category_id'=>'nullable|string','type_id'=>'nullable|string','status_id'=>'nullable|string','priority_id'=>'nullable|string','start_date'=>'nullable|date','due_date'=>'nullable|date','budget_amount'=>'sometimes|numeric','billing_type'=>'nullable|string','progress'=>'sometimes|integer|min:0|max:100']);foreach(['client_party_id'=>'parties','project_manager_id'=>'users'] as $f=>$t)if(array_key_exists($f,$d)){if(!$d[$f]=$this->rid($t,$d[$f]))abort(422,'Invalid relationship UUID.');}foreach(['category_id','type_id','status_id','priority_id'] as $f)if(array_key_exists($f,$d)){if(!$d[$f]=$this->lookup($d[$f]))abort(422,'Invalid lookup UUID.');}return $d;}
 public function projectStore(Request $r){$p=Project::create($this->projectData($r)+['uuid'=>(string)Str::uuid(),'tenant_id'=>$this->tid(),'created_by'=>auth()->id()]);return $this->success(['project'=>$this->pp($p)],'Project created.',201);}
 public function projectUpdate(Request $r,string $uuid){$p=$this->project($uuid);$p->update($this->projectData($r)+['updated_by'=>auth()->id()]);return $this->success(['project'=>$this->pp($p)]);}
 public function projectDelete(string $uuid){$this->project($uuid)->delete();return $this->success(null,'Project archived.');}
 public function projectArchive(Request $r,string $uuid){return $this->projectDelete($uuid);}
 public function tasks(Request $r){$q=Task::where('tenant_id',$this->tid())->withTrashed(false);if($r->filled('search'))$q->where(fn($x)=>$x->where('title','like','%'.$r->search.'%')->orWhere('task_number','like','%'.$r->search.'%'));if($r->filled('assignee'))if($id=$this->rid('users',$r->assignee))$q->where('assigned_to',$id);if($r->boolean('overdue'))$q->where('due_at','<',now())->whereNull('completed_at');return $this->page($q->latest(),$r,fn($x)=>$this->tp($x),'Tasks fetched.');}
 public function taskDashboard(){ $q=Task::where('tenant_id',$this->tid())->withTrashed(false);return $this->success(['dashboard'=>['open'=>(clone $q)->whereNull('completed_at')->count(),'overdue'=>(clone $q)->where('due_at','<',now())->whereNull('completed_at')->count(),'due_today'=>(clone $q)->whereDate('due_at',today())->count(),'tasks'=>(clone $q)->latest()->limit(10)->get()->map(fn($x)=>$this->tp($x))]]);}
 public function taskKanban(){return $this->success(['view'=>'kanban','columns'=>Task::where('tenant_id',$this->tid())->withTrashed(false)->get()->groupBy('status_id')->map(fn($x)=>$x->map(fn($t)=>$this->tp($t))->values())]);}
 public function taskCalendar(){return $this->success(['tasks'=>Task::where('tenant_id',$this->tid())->withTrashed(false)->whereNotNull('due_at')->orderBy('due_at')->get()->map(fn($x)=>$this->tp($x))]);}
 public function taskMy(Request $r){$r->merge(['assignee'=>auth()->user()->uuid]);return $this->tasks($r);}
 public function taskTeam(Request $r){return $this->page(Task::where('tenant_id',$this->tid())->withTrashed(false)->whereNotNull('assigned_team_id')->latest(),$r,fn($x)=>$this->tp($x),'Team tasks fetched.');}
 private function taskData(Request $r,?string $projectUuid=null):array{$d=$r->validate(['task_number'=>'required|string|max:80','title'=>'required|string','description'=>'nullable|string','project_id'=>'nullable|string','parent_task_id'=>'nullable|string','assigned_to'=>'nullable|string','assigned_team_id'=>'nullable|string','status_id'=>'nullable|string','priority_id'=>'nullable|string','category_id'=>'nullable|string','start_at'=>'nullable|date','due_at'=>'nullable|date','progress'=>'sometimes|integer|min:0|max:100']);if($projectUuid)$d['project_id']=$projectUuid;foreach(['project_id'=>'projects','parent_task_id'=>'tasks','assigned_to'=>'users','assigned_team_id'=>'teams'] as $f=>$t)if(array_key_exists($f,$d)&&$d[$f]!==null){if(!$d[$f]=$this->rid($t,$d[$f]))abort(422,'Invalid task relationship UUID.');}foreach(['status_id','priority_id','category_id'] as $f)if(array_key_exists($f,$d)&&$d[$f]!==null){if(!$d[$f]=$this->lookup($d[$f]))abort(422,'Invalid lookup UUID.');}return $d;}
 public function taskShow(string $uuid){$t=$this->task($uuid);$d=$this->tp($t);foreach(['checklists'=>'task_checklists','comments'=>'task_comments','dependencies'=>'task_dependencies','watchers'=>'task_watchers','time_logs'=>'task_time_logs'] as $k=>$tbl)$d[$k]=DB::table($tbl)->where('tenant_id',$this->tid())->where('task_id',$t->id)->get();return $this->success(['task'=>$d]);}
 public function taskStore(Request $r){$t=Task::create($this->taskData($r)+['uuid'=>(string)Str::uuid(),'tenant_id'=>$this->tid(),'created_by'=>auth()->id()]);return $this->success(['task'=>$this->tp($t)],'Task created.',201);}
 public function projectTasks(Request $r,string $uuid){$p=$this->project($uuid);return $this->page(Task::where('tenant_id',$this->tid())->where('project_id',$p->id)->latest(),$r,fn($x)=>$this->tp($x),'Project tasks fetched.');}
 public function projectTaskStore(Request $r,string $uuid){$p=$this->project($uuid);$d=$this->taskData($r);$d['project_id']=$p->id;$t=Task::create($d+['uuid'=>(string)Str::uuid(),'tenant_id'=>$this->tid(),'created_by'=>auth()->id()]);return $this->success(['task'=>$this->tp($t)],'Task created.',201);}
 public function taskUpdate(Request $r,string $uuid){$t=$this->task($uuid);$t->update($this->taskData($r)+['updated_by'=>auth()->id()]);return $this->success(['task'=>$this->tp($t)]);}
 public function taskDelete(string $uuid){$this->task($uuid)->delete();return $this->success(null,'Task archived.');}
 public function taskComplete(string $uuid){$t=$this->task($uuid);$t->update(['progress'=>100,'completed_at'=>now()]);return $this->success(['task'=>$this->tp($t)]);}
 public function taskAssign(Request $r,string $uuid){$t=$this->task($uuid);$d=$r->validate(['assigned_to'=>'nullable|string','assigned_team_id'=>'nullable|string','remarks'=>'nullable|string']);foreach(['assigned_to'=>'users','assigned_team_id'=>'teams'] as $f=>$tbl)if(array_key_exists($f,$d)&&$d[$f]!==null){if(!$d[$f]=$this->rid($tbl,$d[$f]))abort(422,'Invalid assignment UUID.');}$t->update($d+['assigned_by'=>auth()->id()]);DB::table('task_assignments')->insert(['tenant_id'=>$this->tid(),'task_id'=>$t->id,'assigned_to'=>$t->assigned_to,'assigned_team_id'=>$t->assigned_team_id,'assigned_by'=>auth()->id(),'assigned_at'=>now(),'remarks'=>$d['remarks']??null]);return $this->success(['task'=>$this->tp($t)]);}
 public function taskStatus(Request $r,string $uuid){$t=$this->task($uuid);$d=$r->validate(['status_id'=>'required|string','progress'=>'nullable|integer|min:0|max:100']);$d['status_id']=$this->lookup($d['status_id']);if(!$d['status_id'])abort(422,'Invalid status UUID.');$t->update($d);return $this->success(['task'=>$this->tp($t)]);}
 public function taskClone(string $uuid){$t=$this->task($uuid);$d=$t->replicate(['id','uuid','task_number','created_at','updated_at','completed_at','progress']);$d->uuid=(string)Str::uuid();$d->task_number=$t->task_number.'-COPY-'.strtoupper(Str::random(5));$d->created_by=auth()->id();$d->save();return $this->success(['task'=>$this->tp($d)],'Task cloned.',201);}
 public function issueList(Request $r){$q=ClientIssue::where('tenant_id',$this->tid())->withTrashed(false);if($r->filled('search'))$q->where(fn($x)=>$x->where('title','like','%'.$r->search.'%')->orWhere('issue_number','like','%'.$r->search.'%'));return $this->page($q->latest(),$r,fn($x)=>$this->ip($x),'Issues fetched.');}
 public function issueDashboard(){ $q=ClientIssue::where('tenant_id',$this->tid())->withTrashed(false);return $this->success(['dashboard'=>['open'=>(clone $q)->whereNull('closed_at')->count(),'overdue'=>(clone $q)->where('due_at','<',now())->whereNull('closed_at')->count(),'issues'=>(clone $q)->latest()->limit(10)->get()->map(fn($x)=>$this->ip($x))]]);}
 public function issueKanban(){return $this->success(['view'=>'kanban','columns'=>ClientIssue::where('tenant_id',$this->tid())->withTrashed(false)->get()->groupBy('status_id')->map(fn($x)=>$x->map(fn($i)=>$this->ip($i))->values())]);}
 public function issueShow(string $uuid){return $this->success(['issue'=>$this->ip($this->issue($uuid))]);}
 public function issueStore(Request $r){$d=$r->validate(['issue_number'=>'required|string|max:80','client_party_id'=>'required|string','title'=>'required|string','description'=>'nullable|string','project_id'=>'nullable|string','assigned_to'=>'nullable|string','assigned_team_id'=>'nullable|string','due_at'=>'nullable|date']);foreach(['client_party_id'=>'parties','project_id'=>'projects','assigned_to'=>'users','assigned_team_id'=>'teams'] as $f=>$t)if(isset($d[$f])){if(!$d[$f]=$this->rid($t,$d[$f]))abort(422,'Invalid issue relationship UUID.');}$i=ClientIssue::create($d+['uuid'=>(string)Str::uuid(),'tenant_id'=>$this->tid(),'created_by'=>auth()->id()]);return $this->success(['issue'=>$this->ip($i)],'Issue created.',201);}
 public function issueUpdate(Request $r,string $uuid){$i=$this->issue($uuid);$i->update($r->except(['id','uuid','tenant_id','created_by','updated_by']));return $this->success(['issue'=>$this->ip($i)]);}
 public function issueDelete(string $uuid){$this->issue($uuid)->delete();return $this->success(null,'Issue archived.');}
 public function issueState(Request $r,string $uuid,string $state){$i=$this->issue($uuid);if($state==='resolve')$i->update(['resolved_at'=>now()]);elseif($state==='close')$i->update(['closed_at'=>now()]);elseif($state==='reopen')$i->update(['resolved_at'=>null,'closed_at'=>null]);elseif($state==='status'){ $d=$r->validate(['status_id'=>'required|string']);$id=$this->lookup($d['status_id']);if(!$id)abort(422,'Invalid status UUID.');$i->update(['status_id'=>$id]); }else abort(404);return $this->success(['issue'=>$this->ip($i)]);}
 public function issueAssign(Request $r,string $uuid){$i=$this->issue($uuid);$d=$r->validate(['assigned_to'=>'nullable|string','assigned_team_id'=>'nullable|string']);foreach(['assigned_to'=>'users','assigned_team_id'=>'teams'] as $f=>$tbl)if(array_key_exists($f,$d)&&$d[$f]!==null){if(!$d[$f]=$this->rid($tbl,$d[$f]))abort(422,'Invalid assignment UUID.');}$i->update($d);return $this->success(['issue'=>$this->ip($i)]);}
 private function childRelation(string $table,mixed $value,string $field):?int
 {
  if($value===null||$value==='')return null;
  $query=DB::table($table)->where('tenant_id',$this->tid());
  $id=is_numeric($value)?$query->where('id',(int)$value)->value('id'):$query->where('uuid',(string)$value)->value('id');
  if(!$id)abort(422,'Invalid '.$field.'.');
  return (int)$id;
 }
 private function childData(string $parent,string $kind,array $data,int $parentId):array
 {
  if($parent==='projects'){
   $allowed=['members'=>['user_id','team_id','role_id','billing_rate','allocation_percent','joined_at','left_at'],'phases'=>['name','start_date','due_date','status_id','sort_order'],'milestones'=>['phase_id','name','due_date','status_id'],'time-logs'=>['task_id','user_id','started_at','ended_at','minutes','billable'],'expenses'=>['vendor_party_id','amount','currency','expense_date','status_id']];
   $data=array_intersect_key($data,array_flip($allowed[$kind]??[]));
  }
  $relations=$parent==='projects'
   ?['members'=>['user_id'=>'users','team_id'=>'teams','role_id'=>'tenant_lookups'],'phases'=>['status_id'=>'tenant_lookups'],'milestones'=>['status_id'=>'tenant_lookups'],'time-logs'=>['user_id'=>'users','task_id'=>'tasks'],'expenses'=>['vendor_party_id'=>'parties','status_id'=>'tenant_lookups']]
   :['dependencies'=>['depends_on_task_id'=>'tasks'],'watchers'=>['user_id'=>'users'],'time-logs'=>['user_id'=>'users']];
  foreach($relations[$kind]??[] as $field=>$table)if(array_key_exists($field,$data))$data[$field]=$this->childRelation($table,$data[$field],$field);
  if($parent==='projects'&&$kind==='milestones'&&array_key_exists('phase_id',$data)&&$data['phase_id']!==null&&$data['phase_id']!==''){
   $phaseId=DB::table('project_phases')->where('tenant_id',$this->tid())->where('project_id',$parentId)->where('id',(int)$data['phase_id'])->value('id');
   if(!$phaseId)abort(422,'Invalid phase_id.');
   $data['phase_id']=(int)$phaseId;
  }
  return $data;
 }
 public function children(Request $r)
 {
  $parent=(string)$r->route('parent');
  $uuid=(string)($r->route('project_uuid')??$r->route('task_uuid'));
  $kind=(string)$r->route('resource');
  $map=['projects'=>['members'=>'project_members','phases'=>'project_phases','milestones'=>'project_milestones','time-logs'=>'project_time_logs','expenses'=>'project_expenses'],'tasks'=>['checklists'=>'task_checklists','comments'=>'task_comments','dependencies'=>'task_dependencies','watchers'=>'task_watchers','time-logs'=>'task_time_logs']];
  if(!isset($map[$parent][$kind]))abort(404);
  $id=$parent==='projects'?$this->project($uuid)->id:$this->task($uuid)->id;
  $fk=$parent==='projects'?'project_id':'task_id';
  $table=$map[$parent][$kind];
  $query=DB::table($table)->where('tenant_id',$this->tid())->where($fk,$id);
  if($r->isMethod('get'))return $this->success([$kind=>str_contains($kind,'time-')?$query->latest('id')->get():$query->get()]);
  $childId=$r->route('child_id');
  if($r->isMethod('delete')){
   if(!$query->where('id',$childId)->delete())abort(404);
   return $this->success(null,'Deleted.');
  }
  $data=$this->childData($parent,$kind,$r->except(['id','tenant_id',$fk]),$id);
  if($r->isMethod('put')||$r->isMethod('patch')){
   $row=$query->where('id',$childId);
   if(!$row->exists())abort(404);
   if(DB::getSchemaBuilder()->hasColumn($table,'updated_at'))$data['updated_at']=now();
   $row->update($data);
   return $this->success([$kind=>DB::table($table)->where('id',$childId)->first()],'Updated.');
  }
  $data[$fk]=$id;
  $data['tenant_id']=$this->tid();
  if(DB::getSchemaBuilder()->hasColumn($table,'created_at'))$data['created_at']=now();
  if(DB::getSchemaBuilder()->hasColumn($table,'updated_at'))$data['updated_at']=now();
  $new=DB::table($table)->insertGetId($data);
  return $this->success([$kind=>DB::table($table)->where('id',$new)->first()],'Created.',201);
 }
 public function completeMilestone(Request $r)
 {
  $project=$this->project((string)$r->route('project_uuid'));
  $milestone=DB::table('project_milestones')->where('tenant_id',$this->tid())->where('project_id',$project->id)->where('id',$r->route('child_id'));
  if(!$milestone->exists())abort(404);
  $milestone->update(['completed_at'=>now()]);
  return $this->success(['milestone'=>$milestone->first()],'Milestone completed.');
 }
 public function export(){return $this->success(['status'=>'queued','job_id'=>(string)Str::uuid()],'Export queued.',202);}
}
