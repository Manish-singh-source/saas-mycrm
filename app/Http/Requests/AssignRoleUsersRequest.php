<?php
namespace App\Http\Requests;
use App\Http\Requests\ApiFormRequest;
final class AssignRoleUsersRequest extends ApiFormRequest {
 public function authorize(): bool { return true; }
 public function rules(): array { return ['platform_user_ids'=>['required','array','min:1'],'platform_user_ids.*'=>['uuid','exists:platform_users,uuid'],'effective_date'=>['sometimes','nullable','date'],'notify_users'=>['sometimes','boolean'],'audit_reason'=>['nullable','string','max:500']]; }
}