<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FinanceReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
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
            'type' => 'required|in:month,project',
            'month' => 'required_if:type,month|integer|min:1|max:12',
            'year' => 'required_if:type,month|integer|min:2000|max:' . (date('Y') + 1),
            'project_id' => 'required_if:type,project|exists:projects,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ];
    }
}
