<?php
namespace App\Http\Requests;
use Illuminate\Validation\Rule;
final class StorePlatformFileRequest extends ApiFormRequest
{
    public function rules(): array { return ['file'=>['required','file','max:51200'],'disk'=>['sometimes','string','max:80',Rule::in(array_keys(config('filesystems.disks', [])))],'visibility'=>['sometimes',Rule::in(['private','public','tenant'])],'purpose'=>['sometimes','string','max:80']]; }
}
