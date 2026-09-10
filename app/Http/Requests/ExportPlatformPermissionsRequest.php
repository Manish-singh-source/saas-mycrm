<?php
namespace App\Http\Requests;
use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;
final class ExportPlatformPermissionsRequest extends ApiFormRequest {
 public function authorize(): bool { return true; }
 public function rules(): array { return ['format'=>['sometimes',Rule::in(['csv'])],'delivery'=>['sometimes',Rule::in(['job','download'])],'scope'=>['sometimes',Rule::in(['filtered','selected'])],'filters'=>['nullable','array'],'filters.module'=>['nullable','string','max:100'],'filters.guard_name'=>['nullable','string','max:50'],'filters.status'=>['nullable',Rule::in(['active','inactive'])],'sort'=>['nullable',Rule::in(['module','name','display_name','status','created_at','updated_at','created'])],'direction'=>['nullable',Rule::in(['asc','desc'])],'columns'=>['nullable','array'],'columns.*'=>[Rule::in(['uuid','module','name','display_name','guard_name','description','is_system','status','roles_count','created_at','updated_at'])],'selected_ids'=>['required_if:scope,selected','array'],'selected_ids.*'=>['uuid'],'timezone'=>['nullable','timezone'],'email_when_ready'=>['sometimes','boolean']]; }
}