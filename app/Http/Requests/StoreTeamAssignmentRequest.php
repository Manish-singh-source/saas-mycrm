<?php
namespace App\Http\Requests;
final class StoreTeamAssignmentRequest extends ApiFormRequest {
 public function authorize(): bool { return true; }
 public function rules(): array { return ['assignable_type'=>['required','string','max:120'],'assignable_id'=>['required','integer'],'assignment_role'=>['nullable','string','max:80']]; }
}