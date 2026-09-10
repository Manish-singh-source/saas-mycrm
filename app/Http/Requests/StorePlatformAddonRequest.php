<?php
namespace App\Http\Requests;
use Illuminate\Validation\Rule;
final class StorePlatformAddonRequest extends ApiFormRequest {
 public function authorize(): bool { return true; }
 public function rules(): array { return ['name'=>['required','string','max:255'],'pricing_type'=>['required',Rule::in(['recurring','one time','usage based','tiered'])],'price'=>['required','numeric','gt:0'],'currency'=>['nullable','string','size:3'],'is_public'=>['sometimes','boolean'],'status'=>['sometimes',Rule::in(['active','inactive','archived'])]]; }
}
