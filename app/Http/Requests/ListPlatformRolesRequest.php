<?php
namespace App\Http\Requests;
use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;
final class ListPlatformRolesRequest extends ApiFormRequest {
 public function authorize(): bool { return true; }
 public function rules(): array { return ['search'=>['nullable','string','max:150'],'filter'=>['nullable','array'],'filter.status'=>['nullable',Rule::in(['active','inactive'])],'filter.guard_name'=>['nullable','string','max:50'],'filter.type'=>['nullable',Rule::in(['system','custom'])],'sort'=>['nullable',Rule::in(['name','display_name','status','created_at','updated_at'])],'direction'=>['nullable',Rule::in(['asc','desc'])],'per_page'=>['nullable','integer','min:1','max:100'],'scope'=>['nullable',Rule::in(['selected'])],'selected_ids'=>['required_if:scope,selected','array'],'selected_ids.*'=>['uuid']]; }
}