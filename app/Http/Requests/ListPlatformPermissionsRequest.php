<?php
namespace App\Http\Requests;
use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;
final class ListPlatformPermissionsRequest extends ApiFormRequest {
 public function authorize(): bool { return true; }
 public function rules(): array { return ['search'=>['nullable','string','max:150'],'filter'=>['nullable','array'],'filter.module'=>['nullable','string','max:100'],'filter.guard_name'=>['nullable','string','max:50'],'filter.status'=>['nullable',Rule::in(['active','inactive'])],'sort'=>['nullable',Rule::in(['module','name','display_name','status','created_at','updated_at','created'])],'direction'=>['nullable',Rule::in(['asc','desc'])],'per_page'=>['nullable','integer','min:1','max:100'],'scope'=>['nullable',Rule::in(['selected'])],'selected_ids'=>['required_if:scope,selected','array'],'selected_ids.*'=>['uuid']]; }
}