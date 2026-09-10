<?php
namespace App\Http\Requests;
final class ReplacePlatformPlanFeaturesRequest extends ApiFormRequest {
 public function authorize(): bool { return true; }
 public function rules(): array { return ['features'=>['present','array'],'features.*.feature_uuid'=>['required','uuid','exists:features,uuid'],'features.*.value'=>['nullable','string'],'features.*.metadata'=>['nullable','array']]; }
}
