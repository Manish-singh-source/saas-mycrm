<?php
namespace App\Http\Requests;
use Illuminate\Validation\Rule;
final class UpdatePlatformPlanRequest extends ApiFormRequest {
 public function authorize(): bool { return true; }
 public function rules(): array { return ['name'=>['sometimes','string','max:255'],'billing_cycle'=>['sometimes','string','max:50'],'base_price'=>['sometimes','numeric','min:0'],'description'=>['nullable','string'],'currency'=>['sometimes','string','size:3'],'trial_days'=>['sometimes','integer','min:0','max:365'],'is_custom'=>['sometimes','boolean'],'is_public'=>['sometimes','boolean'],'status'=>['sometimes','in:active,inactive,archived'],'features'=>['sometimes','array'],'features.*.feature_uuid'=>['required','uuid','exists:features,uuid'],'features.*.value'=>['nullable','string'],'features.*.metadata'=>['nullable','array'],'addon_uuids'=>['sometimes','array'],'addon_uuids.*'=>['uuid','exists:addon_plans,uuid']]; }
}