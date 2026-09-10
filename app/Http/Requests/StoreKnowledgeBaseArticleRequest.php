<?php
namespace App\Http\Requests;
use Illuminate\Validation\Rule;
final class StoreKnowledgeBaseArticleRequest extends ApiFormRequest {
 public function authorize(): bool { return true; }
 public function rules(): array { return ['category_uuid'=>['nullable','uuid'],'title'=>['required','string','max:255'],'slug'=>['nullable','string','max:255','unique:knowledge_base_articles,slug'],'body'=>['required','string'],'audience'=>['nullable','string','max:50'],'status'=>['sometimes',Rule::in(['draft','published','archived'])]]; }
}
