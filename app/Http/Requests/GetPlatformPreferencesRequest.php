<?php
namespace App\Http\Requests;
use App\Http\Requests\ApiFormRequest;
final class GetPlatformPreferencesRequest extends PlatformCredentialsRequest {
 public function rules(): array { return parent::rules()+['group'=>['nullable','string','max:100'],'key'=>['nullable','string','max:150']]; }
}