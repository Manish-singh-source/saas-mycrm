<?php
namespace App\Http\Requests;
final class ListPlatformActivityLogsRequest extends ApiFormRequest
{
    public function rules(): array { return ['subject_type'=>['sometimes','string','max:120'],'subject_id'=>['sometimes','integer'],'event'=>['sometimes','string','max:120'],'per_page'=>['sometimes','integer','min:1','max:100']]; }
}
