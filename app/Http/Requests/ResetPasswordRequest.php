<?php
namespace App\Http\Requests;
use App\Http\Requests\ApiFormRequest;
final class ResetPasswordRequest extends ApiFormRequest {
 public function authorize(): bool { return true; }
 public function rules(): array { return ['email'=>['required','email'],'token'=>['required','string'],'password'=>['required','string','min:8','confirmed']]; }
}