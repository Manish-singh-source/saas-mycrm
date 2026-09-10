<?php
namespace App\Http\Requests;
use Illuminate\Validation\Rule;
final class ListPlatformTeamRolesRequest extends ApiFormRequest {
 public function authorize(): bool { return true; }
 public function rules(): array { return ['search'=>['nullable','string','max:150'],'filter'=>['nullable','array'],'filter.status'=>['nullable',Rule::in(['active','inactive'])],'sort'=>['nullable',Rule::in(['name','code','status','sort_order','created_at','updated_at'])],'direction'=>['nullable',Rule::in(['asc','desc'])],'per_page'=>['nullable','integer','min:1','max:100']]; }
}