<?php
namespace App\Http\Requests;
use Illuminate\Validation\Rule;
final class StoreKnowledgeBaseCategoryRequest extends ApiFormRequest {
 public function authorize(): bool { return true; }
 public function rules(): array { return ['name'=>['required','string','max:255'],'parent_uuid'=>['nullable','uuid'],'slug'=>['nullable','string','max:255','unique:knowledge_base_categories,slug'],'audience'=>['nullable','string','max:50'],'status'=>['sometimes',Rule::in(['active','inactive'])]]; }
}
