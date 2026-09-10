<?php
namespace App\Http\Requests;
use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;
final class ExportPlatformRolesRequest extends ApiFormRequest {
 public function authorize(): bool { return true; }
 public function rules(): array { return ['format'=>['sometimes',Rule::in(['csv'])],'delivery'=>['sometimes',Rule::in(['job','download'])],'scope'=>['sometimes',Rule::in(['filtered','selected'])],'filters'=>['nullable','array'],'filters.status'=>['nullable',Rule::in(['active','inactive'])],'filters.guard_name'=>['nullable','string','max:50'],'filters.type'=>['nullable',Rule::in(['system','custom'])],'sort'=>['nullable',Rule::in(['name','display_name','status','created_at','updated_at'])],'direction'=>['nullable',Rule::in(['asc','desc'])],'columns'=>['nullable','array'],'columns.*'=>[Rule::in(['uuid','name','display_name','guard_name','is_system','status','permissions_count','users_count','created_at','updated_at'])],'selected_ids'=>['sometimes','array'],'selected_ids.*'=>['uuid'],'timezone'=>['nullable','timezone'],'email_when_ready'=>['sometimes','boolean']]; }
}