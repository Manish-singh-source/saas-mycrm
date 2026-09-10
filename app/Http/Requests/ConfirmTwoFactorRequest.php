<?php
namespace App\Http\Requests;
use App\Http\Requests\ApiFormRequest;
final class ConfirmTwoFactorRequest extends ApiFormRequest {
 public function authorize(): bool { return true; }
 public function rules(): array { return ['setup_token'=>['required','string'],'code'=>['required','digits:6']]; }
}