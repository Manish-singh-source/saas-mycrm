<?php
namespace App\Http\Requests;
use Illuminate\Validation\Rule;
final class UpdateKnowledgeBaseCategoryRequest extends ApiFormRequest {
 public function authorize(): bool { return true; }
 public function rules(): array { return ['name'=>['sometimes','string','max:255'],'slug'=>['sometimes','string','max:255',Rule::unique('knowledge_base_categories','slug')->ignore($this->route('category_uuid'),'uuid')],'audience'=>['sometimes','string','max:50'],'status'=>['sometimes',Rule::in(['active','inactive'])]]; }
}
