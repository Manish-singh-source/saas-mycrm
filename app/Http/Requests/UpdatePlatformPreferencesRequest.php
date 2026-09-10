<?php
namespace App\Http\Requests;
use App\Http\Requests\ApiFormRequest;
final class UpdatePlatformPreferencesRequest extends PlatformCredentialsRequest {
 public function rules(): array { return parent::rules()+['preferences'=>['required','array'],'preferences.*'=>['array']]; }
}