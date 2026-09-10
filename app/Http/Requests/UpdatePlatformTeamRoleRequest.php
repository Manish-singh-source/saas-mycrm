<?php
namespace App\Http\Requests;
use Illuminate\Validation\Rule;
final class UpdatePlatformTeamRoleRequest extends ApiFormRequest {
 public function authorize(): bool { return true; }
 public function rules(): array { return ['name'=>['sometimes','string','max:150'],'description'=>['sometimes','nullable','string'],'permissions'=>['sometimes','array'],'is_system'=>['sometimes','boolean'],'status'=>['sometimes',Rule::in(['active','inactive'])]]; }
}