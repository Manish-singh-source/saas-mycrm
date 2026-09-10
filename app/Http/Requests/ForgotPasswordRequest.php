<?php
namespace App\Http\Requests;
use App\Http\Requests\ApiFormRequest;
final class ForgotPasswordRequest extends ApiFormRequest {
 public function authorize(): bool { return true; }
 public function rules(): array { return ['email'=>['required','email'],'account_ref'=>['nullable','string'],'discovery_token'=>['nullable','string']]; }
}