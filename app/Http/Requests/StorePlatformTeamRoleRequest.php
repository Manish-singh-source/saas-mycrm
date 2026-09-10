<?php
namespace App\Http\Requests;
use Illuminate\Validation\Rule;
final class StorePlatformTeamRoleRequest extends ApiFormRequest {
 public function authorize(): bool { return true; }
 public function rules(): array { return ['name'=>['required','string','max:150'],'description'=>['nullable','string'],'permissions'=>['nullable','array'],'is_system'=>['sometimes','boolean'],'status'=>['sometimes',Rule::in(['active','inactive'])]]; }
}