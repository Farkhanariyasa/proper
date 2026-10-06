<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LowonganResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->job_id,
            'judul_lowongan' => $this->judul_pekerjaan,
            'nama_perusahaan' => $this->nama_perusahaan,
            'deskripsi_pekerjaan' => $this->deskripsi_pekerjaan,
            'tipe_pekerjaan' => $this->tipe_pekerjaan,
            'sistem_kerja' => null,
            'jumlah_kebutuhan' => $this->kuota,
            'jurusan_studi' => null,
            'pengalaman_minimal_tahun' => null,
            'usia_minimal' => null,
            'usia_maksimal' => null,
            'jenis_kelamin' => null,
            'is_disabilitas' => null,
            'persyaratan_tambahan' => null,
            'alamat_lengkap_penempatan' => $this->reg,
            'gaji_tampilkan' => !empty($this->rentang_gaji),
            'gaji_minimal' => $this->rentang_gaji ? (explode('-', $this->rentang_gaji)[0] ?? null) : null,
            'gaji_maksimal' => $this->rentang_gaji ? (explode('-', $this->rentang_gaji)[1] ?? null) : null,
            'status_lowongan' => $this->status_loker,
            'tanggal_buka' => $this->tanggal_tayang?->toISOString(),
            'tanggal_tutup' => $this->tanggal_expired_lowongan?->toISOString(),
            'created_at' => $this->tanggal_dibuat?->toISOString(),
            'updated_at' => $this->tanggal_update?->toISOString(),

            // Eager Loaded Relations
            'kbji' => $this->whenLoaded('kbji', function () {
                return [
                    'id' => $this->kbji->id,
                    'code' => $this->kbji->code,
                    'title' => $this->kbji->title,
                    'level' => $this->kbji->level,
                    'isco_code' => null, // tabel kbji_classifications_2026 tidak memiliki isco_code
                ];
            }),
            'education_level' => $this->whenLoaded('educationLevel', function () {
                return [
                    'id' => $this->educationLevel->id,
                    'name' => $this->educationLevel->name,
                ];
            }),
            'province' => $this->whenLoaded('province', function () {
                return [
                    'id' => $this->province->id,
                    'name' => $this->province->name,
                ];
            }),
            'regency' => $this->whenLoaded('regency', function () {
                return [
                    'id' => $this->regency->id,
                    'name' => $this->regency->name,
                ];
            }),
            'skills' => $this->whenLoaded('skills', function () {
                return $this->skills->map(function ($s) {
                    return [
                        'id' => $s->id,
                        'title' => $s->title,
                        'title_en' => $s->title_en,
                        'tipe_keahlian' => $s->pivot->tipe_keahlian,
                        'level_kemahiran' => $s->pivot->skor ?? 'N/A', // fall back if skor is empty
                    ];
                });
            }),
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                ];
            }),
        ];
    }
}
