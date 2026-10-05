<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreLowonganRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'judul_lowongan' => ['required', 'string', 'max:150'],
            'nama_perusahaan' => ['required', 'string', 'max:150'],
            'kbji_id' => ['required', 'integer', 'exists:kbji_classifications_2026,id'],
            'lapangan_usaha_id' => ['nullable', 'integer'],
            'deskripsi_pekerjaan' => ['required', 'string'],
            'tipe_pekerjaan' => ['required', 'string', 'in:Full-Time,Part-Time,Kontrak,Magang,Freelance'],
            'sistem_kerja' => ['required', 'string', 'in:WFO,WFH,Hybrid'],
            'jumlah_kebutuhan' => ['required', 'integer', 'min:1'],
            'education_level_id' => ['required', 'integer', 'exists:education_levels,id'],
            'jurusan_studi' => ['nullable', 'string', 'max:150'],
            'pengalaman_minimal_tahun' => ['required', 'integer', 'min:0', 'max:50'],
            'usia_minimal' => ['nullable', 'integer', 'min:15', 'max:80'],
            'usia_maksimal' => ['nullable', 'integer', 'min:15', 'max:80', 'gte:usia_minimal'],
            'jenis_kelamin' => ['required', 'string', 'in:Semua,Laki-laki,Perempuan'],
            'is_disabilitas' => ['nullable', 'boolean'],
            'persyaratan_tambahan' => ['nullable', 'string'],
            'provinsi_id' => ['required', 'string', 'size:2', 'exists:provinces,id'],
            'regency_id' => ['required', 'string', 'size:5', 'exists:regencies,id'],
            'alamat_lengkap_penempatan' => ['nullable', 'string'],
            'gaji_tampilkan' => ['nullable', 'boolean'],
            'gaji_minimal' => ['nullable', 'numeric', 'min:0'],
            'gaji_maksimal' => ['nullable', 'numeric', 'min:0', 'gte:gaji_minimal'],
            'status_lowongan' => ['nullable', 'string', 'in:Draft,Published,Closed,Archived'],
            'tanggal_buka' => ['nullable', 'date'],
            'tanggal_tutup' => ['required', 'date', 'after_or_equal:tanggal_buka'],
            'skills' => ['required', 'array', 'min:1'],
            'skills.*.esco_skill_id' => ['required', 'integer', 'exists:skill_nodes,id'],
            'skills.*.tipe_keahlian' => ['required', 'string', 'in:wajib,tambahan'],
            'skills.*.level_kemahiran' => ['nullable', 'string', 'in:pemula,menengah,ahli'],
        ];
    }

    public function messages(): array
    {
        return [
            'judul_lowongan.required' => 'Judul lowongan wajib diisi.',
            'nama_perusahaan.required' => 'Nama perusahaan wajib diisi.',
            'kbji_id.required' => 'Jabatan KBJI wajib dipilih.',
            'kbji_id.exists' => 'Jabatan KBJI yang dipilih tidak terdaftar.',
            'education_level_id.required' => 'Pendidikan minimal wajib dipilih.',
            'education_level_id.exists' => 'Pendidikan yang dipilih tidak valid.',
            'provinsi_id.required' => 'Provinsi penempatan wajib dipilih.',
            'regency_id.required' => 'Kabupaten/Kota penempatan wajib dipilih.',
            'gaji_maksimal.gte' => 'Gaji maksimal harus lebih besar atau sama dengan gaji minimal.',
            'usia_maksimal.gte' => 'Usia maksimal harus lebih besar atau sama dengan usia minimal.',
            'tanggal_tutup.after_or_equal' => 'Tanggal tutup harus sama atau setelah tanggal buka.',
            'skills.required' => 'Minimal tentukan 1 keahlian standar ESCO.',
            'skills.*.esco_skill_id.exists' => 'Skill ESCO yang dipilih tidak valid.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'message' => 'Validasi data lowongan gagal.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
