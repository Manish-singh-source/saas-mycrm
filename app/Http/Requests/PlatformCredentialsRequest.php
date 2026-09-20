<?php
namespace App\Http\Requests;
use App\Http\Requests\ApiFormRequest;
class PlatformCredentialsRequest extends ApiFormRequest {
 public function authorize(): bool { return true; }
 public function rules(): array {
  if ($this->user()) return ['email'=>['sometimes','email'],'password'=>['sometimes','string']];
  return ['email'=>['required','email'],'password'=>['required','string']];
 }
}