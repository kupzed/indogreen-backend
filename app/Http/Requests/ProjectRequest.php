<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Assuming all authenticated users can create/update projects
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'status' => ['required', Rule::in(['Ongoing', 'Prospect', 'Complete', 'Cancel'])],
            'start_date' => 'required|date',
            'finish_date' => 'nullable|date|after_or_equal:start_date',
            'mitra_id' => 'required|exists:partners,id',
            'kategori' => ['required', Rule::in([
                'PLTS Hybrid', 
                'PLTS Ongrid', 
                'PLTS Offgrid', 
                'PJUTS All In One', 
                'PJUTS Two In One', 
                'PJUTS Konvensional'
            ])],
            'lokasi' => 'nullable|string',
            'no_po' => 'nullable|string|max:255',
            'no_so' => 'nullable|string|max:255',
            'is_cert_projects' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama project harus diisi',
            'name.max' => 'Nama project maksimal 255 karakter',
            'description.required' => 'Deskripsi project harus diisi',
            'status.required' => 'Status project harus diisi',
            'status.in' => 'Status project harus salah satu dari: Ongoing, Prospect, Complete, Cancel',
            'start_date.required' => 'Tanggal mulai harus diisi',
            'start_date.date' => 'Tanggal mulai harus berupa tanggal yang valid',
            'finish_date.date' => 'Tanggal selesai harus berupa tanggal yang valid',
            'finish_date.after_or_equal' => 'Tanggal selesai harus sama dengan atau setelah tanggal mulai',
            'mitra_id.required' => 'Mitra harus dipilih',
            'mitra_id.exists' => 'Mitra yang dipilih tidak ditemukan',
            'kategori.required' => 'Kategori project harus diisi',
            'kategori.in' => 'Kategori project harus salah satu dari: PLTS Hybrid, PLTS Ongrid, PLTS Offgrid, PJUTS All In One, PJUTS Two In One, PJUTS Konvensional',
            'is_cert_projects.boolean' => 'Status certificate project harus berupa boolean (true/false)',
        ];
    }
}
