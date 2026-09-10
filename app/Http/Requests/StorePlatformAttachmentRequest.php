<?php
namespace App\Http\Requests;
final class StorePlatformAttachmentRequest extends ApiFormRequest
{
    public function rules(): array { return ['file_uuid'=>['required','uuid'],'attachable_type'=>['required','string','max:120'],'attachable_uuid'=>['required','uuid'],'label'=>['sometimes','nullable','string','max:150']]; }
}
