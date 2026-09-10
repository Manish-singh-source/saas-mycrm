<?php
namespace App\Http\Requests;
use Illuminate\Validation\Rule;
final class ListPlatformTeamsRequest extends ApiFormRequest {
 public function authorize(): bool { return true; }
 public function rules(): array { return ['search'=>['nullable','string','max:150'],'filter'=>['nullable','array'],'filter.status'=>['nullable',Rule::in(['active','inactive'])],'filter.visibility'=>['nullable',Rule::in(['internal','private'])],'status'=>['nullable',Rule::in(['active','inactive'])],'visibility'=>['nullable',Rule::in(['internal','private'])],'sort'=>['nullable',Rule::in(['name','code','status','visibility','created_at','updated_at'])],'direction'=>['nullable',Rule::in(['asc','desc'])],'per_page'=>['nullable','integer','min:1','max:100']]; }
}