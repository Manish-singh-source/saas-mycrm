<?php
namespace App\Services\Rbac;
use App\Support\TenantAudit;
use Illuminate\Http\Request;
final class RbacAuditLogger { public function log(Request $request,string $event,$subject,?array $old=null,?array $new=null,?string $reason=null): void { $tenant=$request->attributes->get('tenant'); TenantAudit::record($request,$tenant,$event,['subject_uuid'=>$subject->uuid,'old'=>$old,'new'=>$new,'reason'=>$reason],true); } }

