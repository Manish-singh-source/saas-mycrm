<?php
namespace App\Http\Requests;
final class ListKnowledgeBaseArticlesRequest extends ApiFormRequest {
 public function authorize(): bool { return true; }
 public function rules(): array { return ['search'=>['nullable','string','max:255'],'status'=>['nullable','in:draft,published,archived'],'per_page'=>['nullable','integer','min:1','max:100']]; }
}