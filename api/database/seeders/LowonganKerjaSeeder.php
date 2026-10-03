<?php

namespace Database\Seeders;

use App\Models\LowonganKerja;
use App\Models\SkillNode;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LowonganKerjaSeeder extends Seeder
{
    public function run(): void
    {
        $vacancies = [
            [
                'judul_lowongan' => 'Senior Software Developer (Fullstack Web)',
                'nama_perusahaan' => 'PT Telkom Digital Solusi',
                'kbji_id' => 38, // 2512 - Pengembang Piranti Lunak (Software Developers)
                'deskripsi_pekerjaan' => 'Bertanggung jawab dalam merancang arsitektur aplikasi berbasis web modern, membangun integrasi backend API dan frontend yang performan dan aman.',
                'tipe_pekerjaan' => 'Full-Time',
                'sistem_kerja' => 'Hybrid',
                'jumlah_kebutuhan' => 3,
                'education_level_id' => 5, // S1 / D4
                'jurusan_studi' => 'Teknik Informatika / Sistem Informasi',
                'pengalaman_minimal_tahun' => 3,
                'usia_minimal' => 22,
                'usia_maksimal' => 38,
                'jenis_kelamin' => 'Semua',
                'is_disabilitas' => false,
                'persyaratan_tambahan' => 'Memiliki portofolio aplikasi web produksi dan pemahaman microservices.',
                'provinsi_id' => '31',
                'regency_id' => '31.71', // Jakarta Pusat
                'alamat_lengkap_penempatan' => 'Jl. Kebon Sirih No. 10, Gambir, Jakarta Pusat',
                'gaji_tampilkan' => true,
                'gaji_minimal' => 15000000.00,
                'gaji_maksimal' => 22000000.00,
                'status_lowongan' => 'Published',
                'tanggal_buka' => now()->subDays(5),
                'tanggal_tutup' => now()->addDays(25),
                'skills' => [
                    ['esco_skill_id' => 119, 'tipe_keahlian' => 'wajib', 'level_kemahiran' => 'ahli'], // Software and Applications Development
                    ['esco_skill_id' => 118, 'tipe_keahlian' => 'wajib', 'level_kemahiran' => 'menengah'], // Database and Network Design
                    ['esco_skill_id' => 296, 'tipe_keahlian' => 'tambahan', 'level_kemahiran' => 'menengah'], // Protecting Privacy and Personal Data
                ],
            ],
            [
                'judul_lowongan' => 'Data Analyst & Pranata Informasi Digital',
                'nama_perusahaan' => 'Mitra Statistik Nusantara',
                'kbji_id' => 38, // 2512
                'deskripsi_pekerjaan' => 'Melakukan pembersihan, eksplorasi data, pemodelan statistik, dan pembuatan dashboard visualisasi data untuk pengambilan keputusan strategis.',
                'tipe_pekerjaan' => 'Full-Time',
                'sistem_kerja' => 'WFO',
                'jumlah_kebutuhan' => 2,
                'education_level_id' => 5, // S1 / D4
                'jurusan_studi' => 'Statistika / Matematika / Ilmu Komputer',
                'pengalaman_minimal_tahun' => 1,
                'usia_minimal' => 21,
                'usia_maksimal' => 32,
                'jenis_kelamin' => 'Semua',
                'is_disabilitas' => true,
                'persyaratan_tambahan' => 'Mampu mengoperasikan SQL dan Python/R serta tools visualisasi BI.',
                'provinsi_id' => '31',
                'regency_id' => '31.74', // Jakarta Selatan
                'alamat_lengkap_penempatan' => 'Kawasan Bisnis Mega Kuningan, Jakarta Selatan',
                'gaji_tampilkan' => true,
                'gaji_minimal' => 8500000.00,
                'gaji_maksimal' => 12500000.00,
                'status_lowongan' => 'Published',
                'tanggal_buka' => now()->subDays(2),
                'tanggal_tutup' => now()->addDays(20),
                'skills' => [
                    ['esco_skill_id' => 277, 'tipe_keahlian' => 'wajib', 'level_kemahiran' => 'ahli'], // Analysing and Evaluating Information and Data
                    ['esco_skill_id' => 290, 'tipe_keahlian' => 'wajib', 'level_kemahiran' => 'menengah'], // Accessing and Analysing Digital Data
                    ['esco_skill_id' => 289, 'tipe_keahlian' => 'tambahan', 'level_kemahiran' => 'menengah'], // Browsing, Searching and Filtering Digital Data
                ],
            ],
            [
                'judul_lowongan' => 'Digital Marketing & Advertising Specialist',
                'nama_perusahaan' => 'PT Media Kreasi Global',
                'kbji_id' => 37, // 2431 - Tenaga Profesional Periklanan dan Pemasaran
                'deskripsi_pekerjaan' => 'Merancang dan mengelola kampanye periklanan digital multi-channel, analisis konversi, dan optimalisasi strategi retensi pengguna.',
                'tipe_pekerjaan' => 'Kontrak',
                'sistem_kerja' => 'Hybrid',
                'jumlah_kebutuhan' => 1,
                'education_level_id' => 4, // D3
                'jurusan_studi' => 'Komunikasi / Manajemen Bisnis / Pemasaran',
                'pengalaman_minimal_tahun' => 1,
                'usia_minimal' => 20,
                'usia_maksimal' => 30,
                'jenis_kelamin' => 'Semua',
                'is_disabilitas' => false,
                'persyaratan_tambahan' => 'Pengalaman mengelola Google Ads dan Meta Ads dengan ROI positif.',
                'provinsi_id' => '31',
                'regency_id' => '31.71', // Jakarta Pusat
                'alamat_lengkap_penempatan' => 'Jl. Thamrin No. 28, Jakarta Pusat',
                'gaji_tampilkan' => true,
                'gaji_minimal' => 7000000.00,
                'gaji_maksimal' => 10000000.00,
                'status_lowongan' => 'Published',
                'tanggal_buka' => now()->subDays(1),
                'tanggal_tutup' => now()->addDays(15),
                'skills' => [
                    ['esco_skill_id' => 277, 'tipe_keahlian' => 'wajib', 'level_kemahiran' => 'menengah'],
                    ['esco_skill_id' => 289, 'tipe_keahlian' => 'wajib', 'level_kemahiran' => 'menengah'],
                ],
            ],
        ];

        foreach ($vacancies as $item) {
            $skills = $item['skills'];
            unset($item['skills']);

            $slug = Str::slug($item['judul_lowongan'] . '-' . $item['nama_perusahaan']) . '-' . Str::lower(Str::random(5));
            $item['slug'] = $slug;

            $lowongan = LowonganKerja::create($item);

            foreach ($skills as $skill) {
                $lowongan->skills()->attach($skill['esco_skill_id'], [
                    'tipe_keahlian' => $skill['tipe_keahlian'],
                    'level_kemahiran' => $skill['level_kemahiran'],
                ]);
            }
        }
    }
}
