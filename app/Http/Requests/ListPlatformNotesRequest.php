<?php
namespace App\Http\Requests;
final class ListPlatformNotesRequest extends ApiFormRequest
{
    public function rules(): array { return ['notable_type'=>['required','string','max:120'],'notable_uuid'=>['required','uuid'],'per_page'=>['sometimes','integer','min:1','max:100']]; }
}
