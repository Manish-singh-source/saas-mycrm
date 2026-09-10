<?php
namespace App\Http\Requests;
final class SyncPlatformUserPermissionsRequest extends ApiFormRequest {
 public function authorize(): bool { return true; }
 public function rules(): array { return ['permission_uuids'=>['sometimes','array'],'permission_uuids.*'=>['uuid','exists:platform_permissions,uuid'],'permission_ids'=>['sometimes','array'],'permission_ids.*'=>['integer','exists:platform_permissions,id'],'audit_reason'=>['nullable','string','max:500']]; }
}