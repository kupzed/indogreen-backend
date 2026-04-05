<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FinanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if ($this->isMethod('get')) {
            return [
                'type' => 'required|in:month,project',
                'month' => 'required_if:type,month|integer|min:1|max:12',
                'year' => 'required_if:type,month|integer|min:2000|max:' . (date('Y') + 1),
                'project_id' => 'required_if:type,project|exists:projects,id',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
            ];
        }

        return [
            'value' => 'required|numeric|min:0',
        ];
    }
}
