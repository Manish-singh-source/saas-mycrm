<?php

namespace App\Http\Requests;

final class StoreLegalDocumentRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'document_type' => ['required', 'string', 'max:80'],
            'title' => ['required', 'string', 'max:255'],
            'version' => ['required', 'string', 'max:40'],
            'content' => ['required', 'string'],
            'status' => ['sometimes', 'in:draft,review'],
        ];
    }
}
