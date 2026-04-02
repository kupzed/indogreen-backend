<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateActivityRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name'          => 'required|string|max:255',
            'short_desc'    => 'nullable|string|max:80',
            'description'   => 'required|string',
            'project_id'    => 'required|exists:projects,id',
            'kategori'      => ['required', Rule::in([
                'Expense Report', 'Invoice', 'Invoice & FP', 'Purchase Order', 'Payment', 'Quotation',
                'Faktur Pajak', 'Kasbon', 'Laporan Teknis', 'Surat Masuk', 'Surat Keluar',
                'Kontrak', 'Berita Acara', 'Receive Item', 'Delivery Order', 'Legalitas', 'Other',
            ])],
            'value'         => 'nullable|numeric|min:0',
            'activity_date' => 'required|date',
            'jenis'         => ['required', Rule::in(['Internal', 'Customer', 'Vendor'])],
            'mitra_id'      => 'nullable|exists:partners,id',
            'from'          => 'nullable|string|max:255',
            'to'            => 'nullable|string|max:255',

            // Multi-file (lampiran baru)
            'attachments.*'             => ['file', 'max:10240'],
            'attachment_names'          => ['array'],
            'attachment_names.*'        => ['nullable', 'string', 'max:255'],
            'attachment_descriptions'   => ['array'],
            'attachment_descriptions.*' => ['nullable', 'string', 'max:500'],

            // Hapus lampiran lama
            'removed_existing_ids'      => ['array'],
            'removed_existing_ids.*'    => ['integer', 'exists:activity_attachments,id'],

            // EDIT lampiran lama (nama & deskripsi)
            'existing_attachment_ids'               => ['array'],
            'existing_attachment_ids.*'             => ['integer', 'exists:activity_attachments,id'],
            'existing_attachment_names'             => ['array'],
            'existing_attachment_names.*'           => ['nullable', 'string', 'max:255'],
            'existing_attachment_descriptions'      => ['array'],
            'existing_attachment_descriptions.*'    => ['nullable', 'string', 'max:500'],
        ];
    }
    
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Business logic from Controller: if jenis === 'Internal', then mitra_id = 1
        if ($this->jenis === 'Internal') {
            $this->merge([
                'mitra_id' => 1,
            ]);
        }
    }
}
