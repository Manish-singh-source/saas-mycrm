<?php
namespace App\Http\Requests;
use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;
final class UpdatePlatformRoleRequest extends ApiFormRequest {
 public function authorize(): bool { return true; }
 public function rules(): array { $uuid=$this->route('role_uuid'); return ['name'=>['sometimes','string','max:150',Rule::unique('platform_roles','name')->ignore($uuid,'uuid')->where(fn($q)=>$q->where('guard_name',$this->input('guard_name','platform')))],'display_name'=>['sometimes','string','max:150'],'guard_name'=>['sometimes','string','max:50'],'description'=>['sometimes','nullable','string'],'is_system'=>['sometimes','boolean'],'status'=>['sometimes',Rule::in(['active','inactive'])],'permission_ids'=>['sometimes','array'],'permission_ids.*'=>['uuid','exists:platform_permissions,uuid'],'audit_reason'=>['nullable','string','max:500']]; }
}