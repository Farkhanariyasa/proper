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
            'slug' => $this->slug,
            'judul_lowongan' => $this->judul_lowongan,
            'nama_perusahaan' => $this->nama_perusahaan,
            'deskripsi_pekerjaan' => $this->deskripsi_pekerjaan,
            'tipe_pekerjaan' => $this->tipe_pekerjaan,
            'sistem_kerja' => $this->sistem_kerja,
            'jumlah_kebutuhan' => $this->jumlah_kebutuhan,
            'jurusan_studi' => $this->jurusan_studi,
            'pengalaman_minimal_tahun' => $this->pengalaman_minimal_tahun,
            'usia_minimal' => $this->usia_minimal,
            'usia_maksimal' => $this->usia_maksimal,
            'jenis_kelamin' => $this->jenis_kelamin,
            'is_disabilitas' => $this->is_disabilitas,
            'persyaratan_tambahan' => $this->persyaratan_tambahan,
            'alamat_lengkap_penempatan' => $this->alamat_lengkap_penempatan,
            'gaji_tampilkan' => $this->gaji_tampilkan,
            'gaji_minimal' => $this->gaji_minimal,
            'gaji_maksimal' => $this->gaji_maksimal,
            'status_lowongan' => $this->status_lowongan,
            'tanggal_buka' => $this->tanggal_buka?->toISOString(),
            'tanggal_tutup' => $this->tanggal_tutup?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Eager Loaded Relations
            'kbji' => $this->whenLoaded('kbji', function () {
                return [
                    'id' => $this->kbji->id,
                    'code' => $this->kbji->code,
                    'title' => $this->kbji->title,
                    'level' => $this->kbji->level,
                    'isco_code' => $this->kbji->isco_code,
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
                        'level_kemahiran' => $s->pivot->level_kemahiran,
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
