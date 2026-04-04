<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExtractDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Permission middleware checks are already in ActivityController construct.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'document' => [
                'required',
                'file',
                'max:10240', // 10 MB
                'mimes:jpeg,jpg,png,gif,webp,pdf,doc,docx,xls,xlsx,txt',
            ],
            'project_id' => 'nullable|integer|exists:projects,id',
        ];
    }
}
