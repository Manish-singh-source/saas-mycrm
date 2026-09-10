<?php
namespace App\Http\Requests;
final class ListPlatformPlansRequest extends ApiFormRequest {
 public function authorize(): bool { return true; }
 public function rules(): array { return ['search'=>['nullable','string','max:255'],'status'=>['nullable','in:active,inactive,archived'],'billing_cycle'=>['nullable','string','max:50'],'currency'=>['nullable','string','size:3'],'per_page'=>['nullable','integer','min:1','max:100']]; }
}
