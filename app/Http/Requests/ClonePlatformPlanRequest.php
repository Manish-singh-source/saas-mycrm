<?php
namespace App\Http\Requests;
use Illuminate\Validation\Rule;
final class ClonePlatformPlanRequest extends ApiFormRequest {
 public function authorize(): bool { return true; }
 public function rules(): array { return ['name'=>['required','string','max:255'],'billing_cycle'=>['required','string','max:50'],'base_price'=>['required','numeric','min:0'],'description'=>['nullable','string'],'currency'=>['nullable','string','size:3'],'trial_days'=>['sometimes','integer','min:0','max:365'],'is_custom'=>['sometimes','boolean'],'is_public'=>['sometimes','boolean'],'status'=>['sometimes','in:active,inactive,archived'],'copy_features'=>['sometimes','boolean']]; }
}