<?php
namespace App\Http\Requests;
final class BulkDeletePlatformPlansRequest extends ApiFormRequest {
 public function authorize(): bool { return true; }
 public function rules(): array { return ['plan_uuids'=>['required','array','min:1'],'plan_uuids.*'=>['uuid','exists:plans,uuid']]; }
}
