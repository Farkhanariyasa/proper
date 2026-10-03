<?php

namespace App\Http\Requests;

use App\Models\JobSeeker;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreJobSeekerRequest extends FormRequest
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
            'nik' => [
                'required',
                'numeric',
                'digits:16',
                'unique:job_seekers,nik',
            ],
            'full_name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:20'],
            'birth_date' => ['required', 'date', 'before:today'],
            'gender' => ['required', 'in:L,P'],
            'regency_id' => ['required', 'string', 'size:5', 'exists:regencies,id'],
            'education_level_id' => ['required', 'integer', 'exists:education_levels,id'],
            'study_field_group' => ['required', 'string', Rule::in(JobSeeker::STUDY_FIELD_GROUPS)],
            'study_field_detail' => ['nullable', 'string', 'max:150'],
            'experience_range' => ['required', 'string', Rule::in(JobSeeker::EXPERIENCE_RANGES)],
            'desired_occupation' => ['nullable', 'string', 'max:150'],
            'kbji_id' => ['nullable', 'integer', 'exists:kbji_classifications,id'],
            'trainings' => ['nullable', 'array'],
            'trainings.*.name' => ['required_with:trainings', 'string', 'max:150'],
            'trainings.*.organizer' => ['nullable', 'string', 'max:150'],
            'trainings.*.year' => ['nullable', 'integer', 'min:1950', 'max:2100'],
            'certifications' => ['nullable', 'array'],
            'certifications.*.name' => ['required_with:certifications', 'string', 'max:150'],
            'certifications.*.type' => ['nullable', 'string', 'max:100'],
            'certifications.*.year' => ['nullable', 'integer', 'min:1950', 'max:2100'],
            'skills' => ['required', 'array', 'min:1'],
            'skills.*' => ['required', 'integer', 'exists:skill_nodes,id'],
            'created_by' => ['nullable', 'exists:users,id'],
        ];
    }

    /**
     * Custom validation messages
     */
    public function messages(): array
    {
        return [
            'nik.required' => 'NIK wajib diisi.',
            'nik.digits' => 'NIK harus tepat 16 digit numerik.',
            'nik.numeric' => 'NIK hanya boleh memuat angka numerik.',
            'nik.unique' => 'NIK ini sudah terdaftar di sistem.',
            'regency_id.exists' => 'Kabupaten/Kota yang dipilih tidak valid.',
            'education_level_id.exists' => 'Jenjang pendidikan yang dipilih tidak valid.',
            'study_field_group.in' => 'Rumpun bidang pendidikan harus sesuai pilihan standar.',
            'experience_range.in' => 'Rentang pengalaman tidak valid.',
            'skills.required' => 'Minimal pilih 1 keahlian (skill).',
            'skills.*.exists' => 'Salah satu skill yang dipilih tidak terdaftar di sistem taksonomi ESCO.',
        ];
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
