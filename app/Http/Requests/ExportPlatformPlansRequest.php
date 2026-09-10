<?php
namespace App\Http\Requests;
use Illuminate\Validation\Rule;
final class ExportPlatformPlansRequest extends ApiFormRequest {
 public function authorize(): bool { return true; }
 public function rules(): array { return ['format'=>['sometimes',Rule::in(['csv'])],'filters'=>['nullable','array'],'filters.status'=>['nullable',Rule::in(['active','inactive','archived'])],'filters.billing_cycle'=>['nullable','string','max:50'],'filters.currency'=>['nullable','string','size:3']]; }
}
