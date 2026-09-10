<?php
namespace App\Http\Requests;
final class ReplacePlatformPlanAddonsRequest extends ApiFormRequest {
 public function authorize(): bool { return true; }
 public function rules(): array { return ['addon_uuids'=>['present','array'],'addon_uuids.*'=>['uuid','exists:addon_plans,uuid']]; }
}
