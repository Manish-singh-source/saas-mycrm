<?php
namespace App\Http\Requests;
use Illuminate\Validation\Rule;
final class ListPlatformUsersRequest extends ApiFormRequest {
 public function authorize(): bool { return true; }
 public function rules(): array { return ['search'=>['nullable','string','max:200'],'filter'=>['nullable','array'],'filter.status'=>['nullable',Rule::in(['active','inactive','suspended'])],'filter.department'=>['nullable','integer','exists:platform_departments,id'],'per_page'=>['nullable','integer','min:1','max:100']]; }
}