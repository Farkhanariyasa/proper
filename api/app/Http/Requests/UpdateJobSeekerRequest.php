<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateJobSeekerRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'full_name' => ['sometimes', 'required', 'string', 'max:150'],
            'gender' => ['nullable', 'in:L,P,Laki-laki,Perempuan'],
            'jenis_kelamin' => ['nullable', 'string', 'max:20'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'umur' => ['nullable', 'integer', 'min:15', 'max:80'],
            'province_id' => ['nullable', 'string', 'size:2', 'exists:provinces,id'],
            'regency_id' => ['nullable', 'string', 'size:5', 'exists:regencies,id'],
            'education_level_id' => ['nullable', 'integer', 'exists:education_levels,id'],
            'jurusan' => ['nullable', 'string', 'max:150'],
            'nama_sekolah' => ['nullable', 'string', 'max:150'],
            'experience' => ['nullable', 'string', 'max:255'],
            'keahlian' => ['nullable', 'string'],
            'sertifikasi' => ['nullable', 'string'],
            'lembaga_pelatihan' => ['nullable', 'string'],
            'progpel' => ['nullable', 'string'],
            'status_bekerja' => ['nullable', 'string', 'max:100'],
            'skills' => ['nullable', 'array'],
            'skills.*' => ['integer', 'exists:skill_nodes,id'],
        ];
    }

    /**
     * Handle failed validation for API response
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'message' => 'Validasi pembaruan profil gagal.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
