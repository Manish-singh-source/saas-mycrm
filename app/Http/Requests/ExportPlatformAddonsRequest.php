<?php
namespace App\Http\Requests;
use Illuminate\Validation\Rule;
final class ExportPlatformAddonsRequest extends ApiFormRequest {
 public function authorize(): bool { return true; }
 public function rules(): array { return ['format'=>['sometimes',Rule::in(['csv'])],'filters'=>['nullable','array'],'filters.status'=>['nullable',Rule::in(['active','inactive','archived'])]]; }
}
