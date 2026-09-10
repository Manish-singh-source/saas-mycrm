<?php

namespace App\Http\Requests;

final class UpdateLegalDocumentRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'document_type' => ['sometimes', 'string', 'max:80'],
            'title' => ['sometimes', 'string', 'max:255'],
            'version' => ['sometimes', 'string', 'max:40'],
            'content' => ['sometimes', 'string'],
            'status' => ['sometimes', 'in:draft,review,superseded,archived'],
        ];
    }
}
