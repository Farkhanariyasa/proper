<?php

namespace App\Http\Requests\Concerns;

/**
 * Aturan validasi kolom profil pencari kerja (tabel req_pk_pencaker),
 * dipakai bersama oleh form Tambah (StoreJobSeekerRequest) dan Edit (UpdateJobSeekerRequest).
 */
trait JobSeekerFields
{
    /** Nilai kategori sesuai data req_pk_pencaker */
    public const GENDERS = ['Laki-laki', 'Perempuan'];
    public const MARITAL = ['BELUM MENIKAH', 'MENIKAH', 'JANDA', 'DUDA'];
    public const KONDISI_FISIK = ['Non Disabilitas', 'Disabilitas'];
    public const YA_TIDAK = ['Ya', 'Tidak'];

    protected function jobSeekerFieldRules(): array
    {
        return [
            'full_name' => ['string', 'max:150'],
            'gender' => ['in:' . implode(',', self::GENDERS)],
            'umur' => ['nullable', 'integer', 'min:15', 'max:80'],
            'province_id' => ['string', 'size:2', 'exists:provinces,id'],
            'regency_id' => ['string', 'size:5', 'exists:regencies,id'],
            'education_level_id' => ['integer', 'exists:education_levels,id'],
            'marital' => ['nullable', 'in:' . implode(',', self::MARITAL)],
            'kondisi_fisik' => ['nullable', 'in:' . implode(',', self::KONDISI_FISIK)],
            'jenis_disabilitas' => ['nullable', 'string', 'max:100'],
            'status_bekerja' => ['nullable', 'string', 'max:100'],
            'jurusan' => ['nullable', 'string', 'max:150'],
            'nama_sekolah' => ['nullable', 'string', 'max:150'],
            'experience' => ['nullable', 'string', 'max:5000'],
            'sertifikasi' => ['nullable', 'string', 'max:2000'],
            'lembaga_pelatihan' => ['nullable', 'string', 'max:2000'],
            'progpel' => ['nullable', 'string', 'max:2000'],
            'keahlian' => ['nullable', 'string', 'max:5000'],
            'bahasa' => ['nullable', 'string', 'max:255'],
            'rencana_kerja_luar_negeri' => ['nullable', 'in:' . implode(',', self::YA_TIDAK)],
            'country_wish' => ['nullable', 'string', 'max:150'],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'Nama lengkap wajib diisi.',
            'gender.required' => 'Jenis kelamin wajib dipilih.',
            'gender.in' => 'Jenis kelamin tidak valid.',
            'umur.min' => 'Umur minimal 15 tahun.',
            'umur.max' => 'Umur maksimal 80 tahun.',
            'province_id.required' => 'Provinsi wajib dipilih.',
            'province_id.exists' => 'Provinsi yang dipilih tidak valid.',
            'regency_id.required' => 'Kabupaten/Kota wajib dipilih.',
            'regency_id.exists' => 'Kabupaten/Kota yang dipilih tidak valid.',
            'education_level_id.required' => 'Pendidikan terakhir wajib dipilih.',
            'education_level_id.exists' => 'Jenjang pendidikan yang dipilih tidak valid.',
            'marital.in' => 'Status perkawinan tidak valid.',
            'kondisi_fisik.in' => 'Kondisi fisik tidak valid.',
            'rencana_kerja_luar_negeri.in' => 'Pilihan rencana kerja luar negeri tidak valid.',
        ];
    }
}
