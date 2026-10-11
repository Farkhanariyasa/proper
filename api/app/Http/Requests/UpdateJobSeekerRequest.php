<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\JobSeekerFields;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateJobSeekerRequest extends FormRequest
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
     * Sama dengan form Tambah, tetapi hanya kolom yang dikirim yang divalidasi
     * (form Edit mengirim kolom yang berubah saja).
     */
    public function rules(): array
    {
        $rules = $this->jobSeekerFieldRules();
        foreach (['full_name', 'gender', 'province_id', 'regency_id', 'education_level_id'] as $field) {
            array_unshift($rules[$field], 'sometimes', 'required');
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
            'message' => 'Validasi pembaruan profil gagal.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
