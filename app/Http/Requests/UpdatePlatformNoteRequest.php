<?php
namespace App\Http\Requests;
use Illuminate\Validation\Rule;
final class UpdatePlatformNoteRequest extends ApiFormRequest
{
    public function rules(): array { return ['note'=>['sometimes','required_without:body','string'],'body'=>['sometimes','required_without:note','string'],'visibility'=>['sometimes',Rule::in(['private','team','tenant','client'])]]; }
}
