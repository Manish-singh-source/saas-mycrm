<?php
namespace App\Http\Requests;
use App\Http\Requests\ApiFormRequest;
final class ReplaceRolePermissionsRequest extends ApiFormRequest {
 public function authorize(): bool { return true; }
 public function rules(): array { return ['permission_ids'=>['required','array','min:1'],'permission_ids.*'=>['uuid','exists:platform_permissions,uuid'],'audit_reason'=>['nullable','string','max:500']]; }
}