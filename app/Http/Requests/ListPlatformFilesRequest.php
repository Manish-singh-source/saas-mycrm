<?php
namespace App\Http\Requests;
use Illuminate\Validation\Rule;
final class ListPlatformFilesRequest extends ApiFormRequest
{
    public function rules(): array { return ['search'=>['sometimes','string','max:255'],'filter.visibility'=>['sometimes',Rule::in(['private','public','tenant'])],'filter.mime_type'=>['sometimes','string','max:150'],'per_page'=>['sometimes','integer','min:1','max:100']]; }
}
