<?php
namespace App\Http\Requests;
final class ListPlatformAttachmentsRequest extends ApiFormRequest
{
    public function rules(): array { return ['attachable_type'=>['required','string','max:120'],'attachable_uuid'=>['required','uuid'],'per_page'=>['sometimes','integer','min:1','max:100']]; }
}
