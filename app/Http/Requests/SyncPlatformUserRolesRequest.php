<?php
namespace App\Http\Requests;
final class SyncPlatformUserRolesRequest extends ApiFormRequest {
 public function authorize(): bool { return true; }
 public function rules(): array { return ['role_uuids'=>['sometimes','array'],'role_uuids.*'=>['uuid','exists:platform_roles,uuid'],'role_ids'=>['sometimes','array'],'role_ids.*'=>['integer','exists:platform_roles,id']]; }
}