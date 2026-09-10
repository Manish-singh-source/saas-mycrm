<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Shared\BaseApiController;
use App\Models\Role;
use App\Models\Staff;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class TenantStaffController extends BaseApiController
{
    public function __construct(private readonly TenantContext $tenant) {}
    private function tid(): int { return $this->tenant->id(); }
    private function findUser(string $uuid): User { return User::where('tenant_id',$this->tid())->where('uuid',$uuid)->firstOrFail(); }
    private function staff(string $uuid): Staff { return Staff::where('tenant_id',$this->tid())->where('uuid',$uuid)->firstOrFail(); }
    private function rel(string $table, string $uuid): ?int { return DB::table($table)->where('tenant_id',$this->tid())->where('uuid',$uuid)->value('id'); }
    private function userPayload(User $user): array {
        $user->load(['staff','defaultOffice','roles']);
        $data=$user->makeHidden(['password','remember_token','two_factor_secret','two_factor_recovery_codes'])->toArray();
        $data['staff']=$user->staff?->only(['uuid','employee_code','display_name','work_email','employment_status']);
        $data['default_office']=$user->defaultOffice?->only(['uuid','office_name','office_code']);
        $data['roles']=$user->roles->map->only(['uuid','name','display_name','status'])->values()->all();
        return $data;
    }
    private function staffPayload(Staff $staff): array {
        $staff->load(['department','designation','office','primaryTeam','users.roles','reportingManager']);
        $data=$staff->toArray();
        $data['department']=$staff->department?->only(['uuid','name','code']);
        $data['designation']=$staff->designation?->only(['uuid','name','code']);
        $data['office']=$staff->office?->only(['uuid','office_name','office_code']);
        $data['primary_team']=$staff->primaryTeam?->only(['uuid','name','code']);
        $data['reporting_manager']=$staff->reportingManager?->only(['uuid','display_name','email']);
        $data['users']=$staff->users->map(fn(User $u)=>$this->userPayload($u))->values()->all();
        return $data;
    }
    public function users(Request $request) {
        $q=User::where('tenant_id',$this->tid())->with(['staff','defaultOffice','roles']);
        if($request->filled('search')) $q->where(fn($x)=>$x->where('display_name','like','%'.$request->search.'%')->orWhere('email','like','%'.$request->search.'%'));
        foreach(['status','account_type'] as $f) if($request->filled('filter.'.$f)) $q->where($f,$request->input('filter.'.$f));
        $p=$q->orderBy('display_name')->paginate((int)$request->integer('per_page',25));
        return $this->list($p->getCollection()->map(fn(User $u)=>$this->userPayload($u))->all(),$p,'Users fetched.');
    }
    public function user(string $uuid) { $u=$this->findUser($uuid); return $this->success(['user'=>$this->userPayload($u),'roles'=>$u->roles->map->only(['uuid','name','display_name','status'])->values()->all()]); }
    public function invite(Request $request) {
        $d=$request->validate(['first_name'=>'required|string|max:100','last_name'=>'nullable|string|max:100','display_name'=>'nullable|string|max:200','email'=>'required|email|max:150','mobile'=>'nullable|string|max:20','staff_id'=>'nullable|string','default_office_id'=>'nullable|string','account_type'=>'sometimes|in:owner,staff,client','status'=>'sometimes|in:invited,active,inactive,suspended','role_ids'=>'sometimes|array']);
        if(User::where('tenant_id',$this->tid())->where('email',$d['email'])->exists()) return $this->businessError('Email already exists for this tenant.','TENANT_EMAIL_EXISTS');
        $temp=Str::random(16); $u=new User; $u->forceFill(['uuid'=>(string)Str::uuid(),'tenant_id'=>$this->tid(),'first_name'=>$d['first_name'],'last_name'=>$d['last_name']??null,'display_name'=>$d['display_name']??trim($d['first_name'].' '.($d['last_name']??'')),'email'=>strtolower($d['email']),'mobile'=>$d['mobile']??null,'staff_id'=>isset($d['staff_id'])?$this->rel('staff',$d['staff_id']):null,'default_office_id'=>isset($d['default_office_id'])?$this->rel('tenant_offices',$d['default_office_id']):null,'account_type'=>$d['account_type']??'staff','status'=>$d['status']??'invited','password'=>Hash::make($temp),'created_by'=>auth()->id()])->save();
        $this->syncRoles($u,$d['role_ids']??[]);
        $data=['user'=>$this->userPayload($u)];
        if(app()->environment('local')) $data['temporary_password']=$temp;
        return $this->success($data,'User invited.',201);
    }
    public function updateUser(Request $request,string $uuid) {
        $u=$this->findUser($uuid); $d=$request->validate(['first_name'=>'sometimes|string|max:100','last_name'=>'nullable|string|max:100','display_name'=>'sometimes|string|max:200','mobile'=>'nullable|string|max:20','staff_id'=>'nullable|string','default_office_id'=>'nullable|string','timezone'=>'sometimes|string|max:100','locale'=>'sometimes|string|max:20','status'=>'sometimes|in:invited,active,inactive,suspended']);
        foreach(['staff_id'=>'staff','default_office_id'=>'tenant_offices'] as $f=>$t) if(array_key_exists($f,$d)) $d[$f]=$d[$f]===null?null:$this->rel($t,$d[$f]);
        $u->forceFill($d)->save(); return $this->success(['user'=>$this->userPayload($u->fresh())],'User updated.');
    }
    private function syncRoles(User $u,array $uuids): void {
        $roles=Role::where('tenant_id',$this->tid())->where('guard_name','tenant')->whereIn('uuid',$uuids)->get();
        DB::table('model_has_roles')->where(['tenant_id'=>$this->tid(),'model_id'=>$u->id,'model_type'=>User::class])->delete();
        foreach($roles as $r) DB::table('model_has_roles')->insert(['tenant_id'=>$this->tid(),'role_id'=>$r->id,'model_id'=>$u->id,'model_type'=>User::class]);
    }
    public function replaceUserRoles(Request $request,string $uuid) { $u=$this->findUser($uuid); $d=$request->validate(['role_ids'=>'required|array','role_ids.*'=>'string']); $this->syncRoles($u,$d['role_ids']); return $this->success(['user'=>$this->userPayload($u->fresh())],'User roles updated.'); }
    private function setUserStatus(string $uuid,string $status) { $u=$this->findUser($uuid); $u->forceFill(['status'=>$status])->save(); return $this->success(['user'=>$this->userPayload($u->fresh())],'User '.$status.'.'); }
    public function suspendUser(string $uuid){return $this->setUserStatus($uuid,'suspended');}
    public function activateUser(string $uuid){return $this->setUserStatus($uuid,'active');}
    public function resetPassword(string $uuid){$u=$this->findUser($uuid);$temp=Str::random(16);$u->forceFill(['password'=>Hash::make($temp)])->save();return $this->success(app()->environment('local')?['temporary_password'=>$temp]:null,'Password reset.');}
    public function forceLogout(string $uuid){$u=$this->findUser($uuid);$u->tokens()->delete();return $this->success(null,'User sessions revoked.');}
    public function requireTwoFactor(Request $request,string $uuid){$u=$this->findUser($uuid);$d=$request->validate(['required'=>'sometimes|boolean']);$u->forceFill(['two_factor_enabled'=>$d['required']??true])->save();return $this->success(['user'=>$this->userPayload($u->fresh())],'Two-factor requirement updated.');}
    private function staffData(Request $request,bool $grid=false) {
        $q=Staff::where('tenant_id',$this->tid())->with(['department','designation','office','primaryTeam','users']);
        if($request->filled('search')) $q->where(fn($x)=>$x->where('display_name','like','%'.$request->search.'%')->orWhere('employee_code','like','%'.$request->search.'%')->orWhere('work_email','like','%'.$request->search.'%'));
        foreach(['employment_status','employment_type'] as $f) if($request->filled('filter.'.$f)) $q->where($f,$request->input('filter.'.$f));
        $p=$q->orderBy('display_name')->paginate((int)$request->integer('per_page',25));
        $rows=$p->getCollection()->map(fn(Staff $s)=>$this->staffPayload($s))->all();
        return $this->list($rows,$p,'Staff fetched.',array_merge($grid?['view'=>'grid']:[],['stats'=>['total'=>Staff::where('tenant_id',$this->tid())->count(),'active'=>Staff::where('tenant_id',$this->tid())->where('employment_status','active')->count(),'inactive'=>Staff::where('tenant_id',$this->tid())->where('employment_status','!=','active')->count()]]));
    }
    public function index(Request $r){return $this->staffData($r);}
    public function grid(Request $r){return $this->staffData($r,true);}
    public function show(string $uuid){return $this->success(['staff'=>$this->staffPayload($this->staff($uuid))]);}
    public function store(Request $request){$d=$request->validate(['employee_code'=>'required|string|max:80','first_name'=>'required|string|max:100','last_name'=>'nullable|string|max:100','display_name'=>'nullable|string|max:200','personal_email'=>'nullable|email','work_email'=>'nullable|email','mobile'=>'nullable|string|max:20','gender'=>'nullable|string|max:30','date_of_birth'=>'nullable|date','joining_date'=>'nullable|date','exit_date'=>'nullable|date','department_id'=>'nullable|string','designation_id'=>'nullable|string','office_id'=>'nullable|string','primary_team_id'=>'nullable|string','reporting_manager_id'=>'nullable|string','employment_type'=>'nullable|string|max:50','employment_status'=>'sometimes|string|max:50','create_user'=>'sometimes|boolean','role_ids'=>'sometimes|array']); $map=['department_id'=>'departments','designation_id'=>'designations','office_id'=>'tenant_offices','primary_team_id'=>'teams','reporting_manager_id'=>'users']; foreach($map as $f=>$t) if(array_key_exists($f,$d)) $d[$f]=$d[$f]===null?null:$this->rel($t,$d[$f]); $s=new Staff; unset($d['create_user'],$d['role_ids']); $s->forceFill(array_merge($d,['uuid'=>(string)Str::uuid(),'tenant_id'=>$this->tid(),'display_name'=>$d['display_name']??trim($d['first_name'].' '.($d['last_name']??'')),'created_by'=>auth()->id()])); $s->save(); if($request->boolean('create_user')) { $u=$this->createLinkedUser($s,$request->input('role_ids',[])); } return $this->success(['staff'=>$this->staffPayload($s->fresh())],'Staff created.',201); }
    private function createLinkedUser(Staff $s,array $roles): User { $temp=Str::random(16); $u=new User; $u->forceFill(['uuid'=>(string)Str::uuid(),'tenant_id'=>$this->tid(),'staff_id'=>$s->id,'first_name'=>$s->first_name,'last_name'=>$s->last_name,'display_name'=>$s->display_name,'email'=>$s->work_email?:$s->personal_email,'password'=>Hash::make($temp),'account_type'=>'staff','status'=>'invited'])->save(); $this->syncRoles($u,$roles); return $u; }
    public function update(Request $request,string $uuid){$s=$this->staff($uuid);$d=$request->except(['uuid','tenant_id','id','create_user','role_ids']);$s->forceFill($d)->save();return $this->success(['staff'=>$this->staffPayload($s->fresh())],'Staff updated.');}
    public function destroy(string $uuid){$s=$this->staff($uuid);$s->users()->update(['status'=>'suspended']);$s->delete();return $this->success(null,'Staff archived.');}
    public function bulkDestroy(Request $r){$d=$r->validate(['ids'=>'required|array|min:1','ids.*'=>'string']);$n=0;foreach($d['ids'] as $id){$s=Staff::where('tenant_id',$this->tid())->where('uuid',$id)->first();if($s){$s->users()->update(['status'=>'suspended']);$s->delete();$n++;}}return $this->success(['archived'=>$n],'Staff archived.');}
    public function restore(string $uuid){$s=Staff::withTrashed()->where('tenant_id',$this->tid())->where('uuid',$uuid)->firstOrFail();$s->restore();return $this->success(['staff'=>$this->staffPayload($s->fresh())],'Staff restored.');}
    public function activity(string $uuid){$s=$this->staff($uuid);$rows=DB::table('activity_logs')->where('tenant_id',$this->tid())->where('subject_type',Staff::class)->where('subject_id',$s->id)->latest()->limit(50)->get();return $this->success(['activities'=>$rows]);}
    private function linkedUser(Staff $s): User { return $s->users()->firstOrFail(); }
    public function staffRoles(string $uuid){$u=$this->linkedUser($this->staff($uuid));return $this->success(['roles'=>$u->roles()->where('tenant_id',$this->tid())->get(['roles.uuid','roles.name','roles.display_name','roles.status'])]);}
    public function replaceStaffRoles(Request $r,string $uuid){$u=$this->linkedUser($this->staff($uuid));$d=$r->validate(['role_ids'=>'required|array','role_ids.*'=>'string']);$this->syncRoles($u,$d['role_ids']);return $this->staffRoles($uuid);}
    public function staffTeams(string $uuid){$s=$this->staff($uuid);$rows=DB::table('team_members')->join('teams','teams.id','=','team_members.team_id')->where('team_members.tenant_id',$this->tid())->where('team_members.staff_id',$s->id)->get(['team_members.*','teams.uuid as team_uuid','teams.name as team_name','teams.code as team_code']);return $this->success(['teams'=>$rows]);}
    public function replaceStaffTeams(Request $r,string $uuid){$s=$this->staff($uuid);$d=$r->validate(['team_ids'=>'required|array','team_ids.*'=>'string']);DB::table('team_members')->where('tenant_id',$this->tid())->where('staff_id',$s->id)->delete();foreach($d['team_ids'] as $id){$tid=$this->rel('teams',$id);if($tid)DB::table('team_members')->insert(['uuid'=>(string)Str::uuid(),'tenant_id'=>$this->tid(),'team_id'=>$tid,'staff_id'=>$s->id,'user_id'=>$s->users()->value('id'),'status'=>'active','created_at'=>now(),'updated_at'=>now()]);}return $this->staffTeams($uuid);}
    private function assignments(string $uuid,string $table,string $key,string $out){$s=$this->staff($uuid);$u=$s->users()->value('id');$q=DB::table($table)->where('tenant_id',$this->tid())->where('user_id',$u);$rows=$q->get();return $this->success([$out=>$rows]);}
    public function staffProjects(string $uuid){return $this->assignments($uuid,'project_members','project_id','projects');}
    public function staffTasks(string $uuid){return $this->assignments($uuid,'task_assignments','task_id','tasks');}
    public function replaceStaffProjects(Request $r,string $uuid){$s=$this->staff($uuid);$u=$this->linkedUser($s);$d=$r->validate(['project_ids'=>'required|array']);DB::table('project_members')->where('tenant_id',$this->tid())->where('user_id',$u->id)->delete();foreach($d['project_ids'] as $id){$pid=$this->rel('projects',$id);if($pid)DB::table('project_members')->insert(['tenant_id'=>$this->tid(),'project_id'=>$pid,'user_id'=>$u->id,'allocation_percent'=>100]);}return $this->staffProjects($uuid);}
    public function replaceStaffTasks(Request $r,string $uuid){$s=$this->staff($uuid);$u=$this->linkedUser($s);$d=$r->validate(['task_ids'=>'required|array']);DB::table('task_assignments')->where('tenant_id',$this->tid())->where('user_id',$u->id)->delete();foreach($d['task_ids'] as $id){$tid=$this->rel('tasks',$id);if($tid)DB::table('task_assignments')->insert(['tenant_id'=>$this->tid(),'task_id'=>$tid,'user_id'=>$u->id,'assigned_at'=>now()]);}return $this->staffTasks($uuid);}
    private function child(string $uuid,string $table){$s=$this->staff($uuid);return DB::table($table)->where('tenant_id',$this->tid())->where('staff_id',$s->id);}
    public function bankAccounts(string $u){return $this->success(['bank_accounts'=>$this->child($u,'staff_bank_accounts')->get()->map(fn($x)=>collect((array)$x)->except('account_number_encrypted')->put('account_number','****')->all())]);}
    public function createBankAccount(Request $r,string $u){$s=$this->staff($u);$d=$r->validate(['account_holder_name'=>'required|string','bank_name'=>'required|string','account_number'=>'required|string','ifsc_code'=>'nullable|string','is_primary'=>'sometimes|boolean']);$d['account_number_encrypted']=encrypt($d['account_number']);unset($d['account_number']);$id=DB::table('staff_bank_accounts')->insertGetId(array_merge($d,['tenant_id'=>$this->tid(),'staff_id'=>$s->id,'created_at'=>now(),'updated_at'=>now()]));return $this->success(['bank_account'=>DB::table('staff_bank_accounts')->find($id)],'Bank account created.',201);}
    public function updateBankAccount(Request $r,string $u,int $id){$d=$r->except(['id','staff_id','tenant_id']);$this->child($u,'staff_bank_accounts')->where('id',$id)->update($d);return $this->bankAccounts($u);}
    public function deleteBankAccount(string $u,int $id){$this->child($u,'staff_bank_accounts')->where('id',$id)->delete();return $this->success(null,'Bank account deleted.');}
    public function salaryStructures(string $u){return $this->success(['salary_structures'=>$this->child($u,'staff_salary_structures')->get()]);}
    public function createSalaryStructure(Request $r,string $u){$s=$this->staff($u);$d=$r->validate(['effective_from'=>'required|date','effective_to'=>'nullable|date','annual_ctc'=>'sometimes|numeric','monthly_gross'=>'sometimes|numeric','currency'=>'sometimes|string|max:3']);$id=DB::table('staff_salary_structures')->insertGetId(array_merge($d,['tenant_id'=>$this->tid(),'staff_id'=>$s->id]));return $this->success(['salary_structure'=>DB::table('staff_salary_structures')->find($id)],'Salary structure created.',201);}
    public function updateSalaryStructure(Request $r,string $u,int $id){$this->child($u,'staff_salary_structures')->where('id',$id)->update($r->except(['id','staff_id','tenant_id']));return $this->salaryStructures($u);}
    public function dashboard(){ $base=Staff::where('tenant_id',$this->tid());return $this->success(['dashboard'=>['cards'=>['active_staff'=>(clone $base)->where('employment_status','active')->count(),'new_joiners'=>(clone $base)->whereDate('joining_date',now()->year.'-'.now()->month.'-01')->count(),'exits'=>(clone $base)->whereNotNull('exit_date')->whereMonth('exit_date',now()->month)->count(),'pending_documents'=>DB::table('staff_documents')->where('tenant_id',$this->tid())->whereNull('expiry_date')->count(),'pending_leave_approvals'=>DB::table('leave_requests')->where('tenant_id',$this->tid())->where('status','pending')->count(),'today_attendance'=>DB::table('attendance_records')->where('tenant_id',$this->tid())->whereDate('attendance_date',today())->count()],'charts'=>[],'pending'=>[]]]); }
    public function import(Request $r){return $this->success(['status'=>'queued','job_id'=>(string)Str::uuid()],'Staff import queued.',202);}
    public function export(Request $r){return $this->success(['status'=>'queued','job_id'=>(string)Str::uuid()],'Staff export queued.',202);}
    public function tab(string $uuid,string $tab){$s=$this->staff($uuid);$map=['documents'=>'staff_documents','bank-details'=>'staff_bank_accounts','salary-structure'=>'staff_salary_structures','attendance'=>'attendance_records','leave-history'=>'leave_requests','payroll'=>'payrolls','certifications'=>'staff_certifications','appraisals'=>'staff_appraisals','training'=>'staff_training','assets'=>'staff_assets'];if($tab==='user-access')return $this->success(['tab'=>$tab,'data'=>['users'=>$s->users->map(fn(User $u)=>$this->userPayload($u))]]);if(isset($map[$tab]))return $this->success(['tab'=>$tab,'data'=>DB::table($map[$tab])->where('tenant_id',$this->tid())->where('staff_id',$s->id)->get()]);return $this->success(['tab'=>$tab,'data'=>[]]);}
}

