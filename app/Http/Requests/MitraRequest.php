<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MitraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'is_pribadi' => ['nullable', 'boolean'],
            'is_perusahaan' => ['nullable', 'boolean'],
            'is_customer' => ['nullable', 'boolean'],
            'is_vendor' => ['nullable', 'boolean'],
            'alamat' => ['required', 'string'],
            'website' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'kontak_1' => ['nullable', 'string', 'max:255'],
            'kontak_1_nama' => ['nullable', 'string', 'max:255'],
            'kontak_1_jabatan' => ['nullable', 'string', 'max:255'],
            'kontak_2_nama' => ['nullable', 'string', 'max:255'],
            'kontak_2' => ['nullable', 'string', 'max:255'],
            'kontak_2_jabatan' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function passedValidation()
    {
        if (!$this->has('is_pribadi') && !$this->has('is_perusahaan') && !$this->has('is_customer') && !$this->has('is_vendor')) {
            abort(response()->json(['message' => 'Minimal satu kategori mitra wajib dipilih.'], 422));
        }
    }
}
