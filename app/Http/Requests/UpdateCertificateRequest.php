<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCertificateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        $certificateId = $this->route('certificate')->id ?? $this->route('certificate');

        return [
            'name'                  => 'required|string|max:255',
            'no_certificate'        => ['required', 'string', 'max:30', Rule::unique('certificates', 'no_certificate')->ignore($certificateId)],
            'project_id'            => 'required|exists:projects,id',
            'barang_certificate_id' => 'required|exists:barang_certificates,id',
            'status'                => ['required', Rule::in(['Belum', 'Tidak Aktif', 'Aktif'])],
            'date_of_issue'         => 'nullable|date',
            'date_of_expired'       => 'nullable|date|after:date_of_issue',

            // Multi-file (lampiran baru)
            'attachments.*'             => ['file', 'max:10240'],
            'attachment_names'          => ['array'],
            'attachment_names.*'        => ['nullable', 'string', 'max:255'],
            'attachment_descriptions'   => ['array'],
            'attachment_descriptions.*' => ['nullable', 'string', 'max:500'],

            // Hapus lampiran lama
            'removed_existing_ids'      => ['array'],
            'removed_existing_ids.*'    => ['integer', 'exists:certificate_attachments,id'],

            // EDIT lampiran lama (nama & deskripsi)
            'existing_attachment_ids'               => ['array'],
            'existing_attachment_ids.*'             => ['integer', 'exists:certificate_attachments,id'],
            'existing_attachment_names'             => ['array'],
            'existing_attachment_names.*'           => ['nullable', 'string', 'max:255'],
            'existing_attachment_descriptions'      => ['array'],
            'existing_attachment_descriptions.*'    => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function prepareForValidation()
    {
        $merge = [];
        if (($this->date_of_issue ?? '') === '') {
            $merge['date_of_issue'] = null;
        }
        if (($this->date_of_expired ?? '') === '') {
            $merge['date_of_expired'] = null;
        }

        if (!empty($merge)) {
            $this->merge($merge);
        }
    }
}
