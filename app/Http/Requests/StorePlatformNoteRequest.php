<?php
namespace App\Http\Requests;
use Illuminate\Validation\Rule;
final class StorePlatformNoteRequest extends ApiFormRequest
{
    public function rules(): array { return ['notable_type'=>['required','string','max:120'],'notable_uuid'=>['required','uuid'],'note'=>['sometimes','required_without:body','string'],'body'=>['sometimes','required_without:note','string'],'visibility'=>['sometimes',Rule::in(['private','team','tenant','client'])]]; }
}
