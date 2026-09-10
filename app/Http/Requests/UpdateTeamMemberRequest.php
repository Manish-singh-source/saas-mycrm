<?php
namespace App\Http\Requests;
use Illuminate\Validation\Rule;
final class UpdateTeamMemberRequest extends ApiFormRequest {
 public function authorize(): bool { return true; }
 public function rules(): array { return ['team_role_uuid'=>['nullable','uuid','exists:platform_team_roles,uuid'],'platform_team_role_id'=>['nullable','integer','exists:platform_team_roles,id'],'joined_at'=>['nullable','date'],'effective_from'=>['nullable','date'],'left_at'=>['nullable','date'],'effective_to'=>['nullable','date'],'status'=>['sometimes',Rule::in(['active','inactive'])]]; }
}