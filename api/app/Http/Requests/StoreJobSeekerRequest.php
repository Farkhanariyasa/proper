<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\JobSeekerFields;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreJobSeekerRequest extends FormRequest
{
    use JobSeekerFields;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Kolom mengikuti tabel req_pk_pencaker. Nama, jenis kelamin, wilayah,
     * dan pendidikan wajib diisi saat menambah profil baru.
     */
    public function rules(): array
    {
        $rules = $this->jobSeekerFieldRules();
        foreach (['full_name', 'gender', 'province_id', 'regency_id', 'education_level_id'] as $required) {
            array_unshift($rules[$required], 'required');
        }
        return $rules;
    }

    /**
     * Handle failed validation for API response
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'message' => 'Validasi formulir gagal.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
