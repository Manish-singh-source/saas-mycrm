<?php
namespace App\Services\Tenant;
use App\Support\TenantAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
final class TenantWorkspaceService {
    private function tenantId(): int { return (int) request()->attributes->get('tenant_id'); }
    public function byUuid(string $table,string $uuid,bool $includeDeleted=false): object { $q=DB::table($table)->where('tenant_id',$this->tenantId())->where('uuid',$uuid); if(!$includeDeleted && in_array($table,['teams','staff'],true))$q->whereNull('deleted_at'); return $q->firstOrFail(); }
    public function uuidToId(string $table,?string $uuid,bool $nullable=true): ?int { if($uuid===null||$uuid===''){ if($nullable)return null; abort(404,'Related resource not found.'); } $q=DB::table($table)->where('uuid',$uuid); if(in_array($table,['teams','departments','tenant_offices','tenant_lookups','users','staff','team_roles'],true))$q->where('tenant_id',$this->tenantId()); $id=$q->value('id'); if(!$id)abort(404,'Related resource not found.'); return (int)$id; }
    public function audit(Request $request,string $event,string $type,int $id,?array $old,?array $new,?string $reason=null): void { TenantAudit::record($request,$request->attributes->get('tenant'),$event,['subject_type'=>$type,'subject_id'=>$id,'old'=>$old,'new'=>$new,'reason'=>$reason],true); }
    public function createJob(Request $request,string $type,string $resource,array $filters): array { $id=DB::table('tenant_import_export_jobs')->insertGetId(['uuid'=>(string)Str::uuid(),'tenant_id'=>$this->tenantId(),'type'=>'export','resource'=>$resource,'status'=>'queued','filters'=>json_encode($filters),'created_by'=>$request->user()?->id,'created_at'=>now(),'updated_at'=>now()]); return ['job_id'=>$id,'status'=>'queued','resource'=>$resource]; }
}

