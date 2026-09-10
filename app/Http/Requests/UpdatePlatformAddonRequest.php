<?php
namespace App\Http\Requests;
use Illuminate\Validation\Rule;
final class UpdatePlatformAddonRequest extends ApiFormRequest {
 public function authorize(): bool { return true; }
 public function rules(): array { return ['name'=>['sometimes','string','max:255'],'pricing_type'=>['sometimes',Rule::in(['recurring','one time','usage based','tiered'])],'price'=>['sometimes','numeric','gt:0'],'currency'=>['sometimes','string','size:3'],'is_public'=>['sometimes','boolean'],'status'=>['sometimes',Rule::in(['active','inactive','archived'])]]; }
}