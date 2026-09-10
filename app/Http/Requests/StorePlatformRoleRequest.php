<?php
namespace App\Http\Requests;
use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;
final class StorePlatformRoleRequest extends ApiFormRequest {
 public function authorize(): bool { return true; }
 public function rules(): array { return ['name'=>['required','string','max:150',Rule::unique('platform_roles')->where(fn($q)=>$q->where('guard_name',$this->input('guard_name','platform')))],'display_name'=>['required','string','max:150'],'guard_name'=>['nullable','string','max:50'],'description'=>['nullable','string'],'is_system'=>['sometimes','boolean'],'status'=>['sometimes',Rule::in(['active','inactive'])],'permission_ids'=>['sometimes','array'],'permission_ids.*'=>['uuid','exists:platform_permissions,uuid'],'audit_reason'=>['nullable','string','max:500']]; }
}