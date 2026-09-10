<?php
namespace App\Http\Requests;
use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;
final class ClonePlatformRoleRequest extends ApiFormRequest {
 public function authorize(): bool { return true; }
 public function rules(): array { return ['name'=>['required','string','max:150',Rule::unique('platform_roles')->where(fn($q)=>$q->where('guard_name',$this->route('role_guard_name','platform')))],'display_name'=>['required','string','max:150'],'copy_permissions'=>['sometimes','boolean'],'copy_description'=>['sometimes','boolean'],'status'=>['sometimes',Rule::in(['active','inactive'])],'audit_reason'=>['nullable','string','max:500']]; }
}