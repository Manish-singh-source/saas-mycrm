<?php
namespace App\Http\Controllers;
use App\Http\Requests\ListPlatformTeamRolesRequest;
use App\Http\Requests\StorePlatformTeamRoleRequest;
use App\Http\Requests\UpdatePlatformTeamRoleRequest;
use App\Models\PlatformTeamRole;
use App\Support\ApiResponse;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class PlatformTeamRoleController extends Controller
{
 public function index(ListPlatformTeamRolesRequest $request): mixed {
  $input=$request->validated(); $query=PlatformTeamRole::query()->orderBy($input['sort']??'name',$input['direction']??'asc');
  if(!empty($input['search']))$query->where(fn($q)=>$q->where('name','like','%'.$input['search'].'%')->orWhere('code','like','%'.$input['search'].'%'));
  if(!empty($input['filter']['status']))$query->where('status',$input['filter']['status']);
  $kpiRows=(clone $query)->withCount('members')->get();
  $kpis=['total'=>$kpiRows->count(),'active'=>$kpiRows->where('status','active')->count(),'inactive'=>$kpiRows->where('status','inactive')->count(),'system'=>$kpiRows->where('is_system',true)->count(),'custom'=>$kpiRows->where('is_system',false)->count(),'assignments'=>$kpiRows->sum('members_count'),'members'=>$kpiRows->sum('members_count'),'permissions'=>$kpiRows->sum(fn($role)=>is_array($role->permissions??null)?count($role->permissions):0)];
  $p=(clone $query)->with(['members.user.department', 'members.user.designation', 'members.team'])->withCount('members')->paginate($request->integer('per_page',25))->withQueryString(); $items=collect($p->items())->map(fn($role)=>$this->decorate($role))->all();
  return ApiResponse::success($items,'Platform team roles fetched.',200,['current_page'=>$p->currentPage(),'per_page'=>$p->perPage(),'total'=>$p->total(),'last_page'=>$p->lastPage(),'kpis'=>$kpis]);
 }
 public function store(StorePlatformTeamRoleRequest $request): mixed {
  $input=$request->validated(); $role=new PlatformTeamRole();
  $role->forceFill(['uuid'=>(string)Str::uuid(),'name'=>$input['name'],'description'=>$input['description']??null,'permissions'=>$input['permissions']??[],'sort_order'=>((int)PlatformTeamRole::max('sort_order'))+1,'is_system'=>$input['is_system']??false,'status'=>$input['status']??'active'])->save();
  ActivityLogger::record($request, 'platform_team_role.created', $role, 'Platform team role created.', ['name' => $role->name, 'code' => $role->code]);
  return ApiResponse::success(['team_role'=>$this->decorate($role->fresh())],'Platform team role created successfully.',201);
 }
 public function show(string $uuid): mixed { $role=$this->find($uuid); if(!$role)return ApiResponse::error('Platform team role not found.',404,null,'PLATFORM_TEAM_ROLE_NOT_FOUND'); return ApiResponse::success(['team_role'=>$this->decorate($role)],'Platform team role fetched.'); }
 public function update(UpdatePlatformTeamRoleRequest $request,string $uuid): mixed {
  $role=$this->find($uuid); if(!$role)return ApiResponse::error('Platform team role not found.',404,null,'PLATFORM_TEAM_ROLE_NOT_FOUND');
  $input=$request->validated(); $data=collect($input)->only(['description','permissions','is_system','status'])->all();
  if(array_key_exists('name',$input)){ $data['name']=$input['name']; }
  $role->forceFill($data)->save();
  ActivityLogger::record($request, 'platform_team_role.updated', $role, 'Platform team role updated.', $data);
  if (array_key_exists('permissions', $input)) ActivityLogger::record($request, 'platform_team_role.permissions_updated', $role, 'Team role permissions updated.', ['permissions' => $input['permissions']]);
  return ApiResponse::success(['team_role'=>$this->decorate($role->fresh())],'Platform team role updated successfully.');
 }
 public function destroy(Request $request, string $uuid): mixed {
  $role=PlatformTeamRole::withTrashed()->where('uuid',$uuid)->first(); if(!$role)return ApiResponse::error('Platform team role not found.',404,null,'PLATFORM_TEAM_ROLE_NOT_FOUND');
  if($role->is_system)return ApiResponse::error('System team roles cannot be deleted.',403,null,'SYSTEM_TEAM_ROLE_DELETE_FORBIDDEN');
  if($role->members()->exists())return ApiResponse::error('Team role is assigned to a member.',409,null,'TEAM_ROLE_IN_USE');
  $role->update(['status'=>'inactive']); $role->delete(); ActivityLogger::record($request, 'platform_team_role.deleted', $role, 'Platform team role deleted.'); return ApiResponse::success(null,'Platform team role archived successfully.');
 }
 private function find(string $uuid): ?PlatformTeamRole { return PlatformTeamRole::where('uuid',$uuid)->first(); }
 private function decorate(PlatformTeamRole $role): PlatformTeamRole { $role->load(['members.user.department','members.user.designation','members.team']); $permissions=$role->permissions??[]; $role->setAttribute('permissions',$permissions); $role->setAttribute('permissions_count',is_array($permissions)?count($permissions):0); $role->setAttribute('members_count',$role->members->count()); return $role; }
}
