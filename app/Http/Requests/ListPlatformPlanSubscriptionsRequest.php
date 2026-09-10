<?php
namespace App\Http\Requests;
final class ListPlatformPlanSubscriptionsRequest extends ApiFormRequest {
 public function authorize(): bool { return true; }
 public function rules(): array { return ['per_page'=>['nullable','integer','min:1','max:100']]; }
}
