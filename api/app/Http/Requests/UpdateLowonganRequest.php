<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateLowonganRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'judul_lowongan' => ['sometimes', 'required', 'string', 'max:150'],
            'nama_perusahaan' => ['sometimes', 'required', 'string', 'max:150'],
            'kbji_id' => ['sometimes', 'required', 'integer', 'exists:kbji_classifications_2026,id'],
            'lapangan_usaha_id' => ['nullable', 'integer'],
            'deskripsi_pekerjaan' => ['sometimes', 'required', 'string'],
            'tipe_pekerjaan' => ['sometimes', 'required', 'string', 'in:Full time,Part time,Contract,Internship'],
            'sistem_kerja' => ['sometimes', 'required', 'string', 'in:WFO,WFH,Hybrid'],
            'jumlah_kebutuhan' => ['sometimes', 'required', 'integer', 'min:1'],
            'education_level_id' => ['sometimes', 'required', 'integer', 'exists:education_levels,id'],
            'jurusan_studi' => ['nullable', 'string', 'max:150'],
            'pengalaman_minimal_tahun' => ['sometimes', 'required', 'integer', 'min:0', 'max:50'],
            'usia_minimal' => ['nullable', 'integer', 'min:15', 'max:80'],
            'usia_maksimal' => ['nullable', 'integer', 'min:15', 'max:80', 'gte:usia_minimal'],
            'jenis_kelamin' => ['sometimes', 'required', 'string', 'in:Semua,Laki-laki,Perempuan'],
            'is_disabilitas' => ['nullable', 'boolean'],
            'persyaratan_tambahan' => ['nullable', 'string'],
            'provinsi_id' => ['sometimes', 'required', 'string', 'size:2', 'exists:provinces,id'],
            'regency_id' => ['sometimes', 'required', 'string', 'size:5', 'exists:regencies,id'],
            'alamat_lengkap_penempatan' => ['nullable', 'string'],
            'gaji_tampilkan' => ['nullable', 'boolean'],
            'gaji_minimal' => ['nullable', 'numeric', 'min:0'],
            'gaji_maksimal' => ['nullable', 'numeric', 'min:0', 'gte:gaji_minimal'],
            'status_lowongan' => ['sometimes', 'required', 'string', 'in:Draft,Published,Closed,Expired,Suspended,Blocked,Archived'],
            'tanggal_buka' => ['nullable', 'date'],
            'tanggal_tutup' => ['sometimes', 'required', 'date', 'after_or_equal:tanggal_buka'],
            'skills' => ['nullable', 'array'],
            'skills.*.esco_skill_id' => ['required_with:skills', 'integer', 'exists:skill_nodes,id'],
            'skills.*.tipe_keahlian' => ['required_with:skills', 'string', 'in:wajib,tambahan'],
            'skills.*.level_kemahiran' => ['nullable', 'string', 'in:pemula,menengah,ahli'],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'message' => 'Validasi pembaruan lowongan gagal.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
