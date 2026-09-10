<?php
namespace App\Http\Requests;
final class SyncPlatformUserTeamsRequest extends ApiFormRequest {
 public function authorize(): bool { return true; }
 public function rules(): array { return ['team_uuids'=>['sometimes','array'],'team_uuids.*'=>['uuid','exists:platform_teams,uuid'],'team_ids'=>['sometimes','array'],'team_ids.*'=>['integer','exists:platform_teams,id']]; }
}