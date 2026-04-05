<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BarangCertificateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('barang_certificate')->id ?? $this->route('barang_certificate');

        return [
            'name' => 'required|string|max:255',
            'no_seri' => [
                'required',
                'string',
                'max:30',
                Rule::unique('barang_certificates', 'no_seri')->ignore($id)
            ],
            'mitra_id' => 'required|exists:partners,id',
        ];
    }
}
