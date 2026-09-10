<?php
namespace App\Http\Requests;
final class BulkDeletePlatformAddonsRequest extends ApiFormRequest {
 public function authorize(): bool { return true; }
 public function rules(): array { return ['addon_uuids'=>['required','array','min:1'],'addon_uuids.*'=>['uuid','exists:addon_plans,uuid']]; }
}
