<?php
namespace App\Http\Requests;
use Illuminate\Validation\Rule;
final class UpdateKnowledgeBaseArticleRequest extends ApiFormRequest {
 public function authorize(): bool { return true; }
 public function rules(): array { return ['category_uuid'=>['nullable','uuid'],'title'=>['sometimes','string','max:255'],'slug'=>['sometimes','string','max:255',Rule::unique('knowledge_base_articles','slug')->ignore($this->route('article_uuid'),'uuid')],'body'=>['sometimes','string'],'audience'=>['sometimes','string','max:50'],'status'=>['sometimes',Rule::in(['draft','published','archived'])]]; }
}
