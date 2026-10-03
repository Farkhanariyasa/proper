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
            // 1. Software Dev Fullstack (IT)
            [
                'judul_lowongan' => 'Senior Software Developer (Fullstack Web)',
                'nama_perusahaan' => 'PT Telkom Digital Solusi',
                'kbji_id' => 38, // 2512 - Pengembang Piranti Lunak
                'deskripsi_pekerjaan' => 'Bertanggung jawab dalam merancang arsitektur aplikasi berbasis web modern, membangun integrasi backend API dan frontend yang performan dan aman.',
                'tipe_pekerjaan' => 'Full-Time',
                'sistem_kerja' => 'Hybrid',
                'jumlah_kebutuhan' => 3,
                'education_level_id' => 5, // S1
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
                    ['esco_skill_id' => 3986, 'tipe_keahlian' => 'wajib', 'level_kemahiran' => 'ahli'], // Javascript
                    ['esco_skill_id' => 8034, 'tipe_keahlian' => 'wajib', 'level_kemahiran' => 'menengah'], // Typescript
                    ['esco_skill_id' => 118, 'tipe_keahlian' => 'tambahan', 'level_kemahiran' => 'menengah'], // Database Design
                ],
            ],
            // 2. Data Analyst & BI
            [
                'judul_lowongan' => 'Data Analyst & Business Intelligence Specialist',
                'nama_perusahaan' => 'PT Mitra Statistik Nusantara',
                'kbji_id' => 59, // 2521.01 - Analis Data & Pemodel Statistik
                'deskripsi_pekerjaan' => 'Melakukan pembersihan, eksplorasi data, pemodelan statistik, dan pembuatan dashboard visualisasi data untuk pengambilan keputusan strategis bisnis.',
                'tipe_pekerjaan' => 'Full-Time',
                'sistem_kerja' => 'WFO',
                'jumlah_kebutuhan' => 2,
                'education_level_id' => 5, // S1
                'jurusan_studi' => 'Statistika / Matematika / Ilmu Komputer',
                'pengalaman_minimal_tahun' => 1,
                'usia_minimal' => 21,
                'usia_maksimal' => 32,
                'jenis_kelamin' => 'Semua',
                'is_disabilitas' => true,
                'persyaratan_tambahan' => 'Mampu mengoperasikan SQL dan Python serta tools visualisasi BI.',
                'provinsi_id' => '31',
                'regency_id' => '31.74', // Jakarta Selatan
                'alamat_lengkap_penempatan' => 'Kawasan Bisnis Mega Kuningan Kav E.4, Jakarta Selatan',
                'gaji_tampilkan' => true,
                'gaji_minimal' => 9000000.00,
                'gaji_maksimal' => 14000000.00,
                'status_lowongan' => 'Published',
                'tanggal_buka' => now()->subDays(2),
                'tanggal_tutup' => now()->addDays(20),
                'skills' => [
                    ['esco_skill_id' => 277, 'tipe_keahlian' => 'wajib', 'level_kemahiran' => 'ahli'], // Analysing Data
                    ['esco_skill_id' => 11810, 'tipe_keahlian' => 'wajib', 'level_kemahiran' => 'menengah'], // Python
                    ['esco_skill_id' => 7153, 'tipe_keahlian' => 'wajib', 'level_kemahiran' => 'menengah'], // SQL Query
                    ['esco_skill_id' => 3735, 'tipe_keahlian' => 'tambahan', 'level_kemahiran' => 'menengah'], // Statistical Analysis
                ],
            ],
            // 3. Digital Marketing Specialist
            [
                'judul_lowongan' => 'Digital Marketing & Growth Specialist',
                'nama_perusahaan' => 'PT Media Kreasi Global',
                'kbji_id' => 53, // 2431.01 - Spesialis Pemasaran Digital
                'deskripsi_pekerjaan' => 'Merancang dan mengelola kampanye periklanan digital multi-channel, analisis konversi, copywriting iklan, dan optimalisasi CAC & ROAS.',
                'tipe_pekerjaan' => 'Kontrak',
                'sistem_kerja' => 'Hybrid',
                'jumlah_kebutuhan' => 1,
                'education_level_id' => 5, // S1
                'jurusan_studi' => 'Komunikasi / Manajemen Bisnis / Pemasaran',
                'pengalaman_minimal_tahun' => 2,
                'usia_minimal' => 22,
                'usia_maksimal' => 32,
                'jenis_kelamin' => 'Semua',
                'is_disabilitas' => false,
                'persyaratan_tambahan' => 'Pengalaman mengelola Google Ads dan Meta Ads dengan track record terbukti.',
                'provinsi_id' => '31',
                'regency_id' => '31.71', // Jakarta Pusat
                'alamat_lengkap_penempatan' => 'Jl. M.H. Thamrin No. 28, Jakarta Pusat',
                'gaji_tampilkan' => true,
                'gaji_minimal' => 8000000.00,
                'gaji_maksimal' => 12000000.00,
                'status_lowongan' => 'Published',
                'tanggal_buka' => now()->subDays(3),
                'tanggal_tutup' => now()->addDays(22),
                'skills' => [
                    ['esco_skill_id' => 4344, 'tipe_keahlian' => 'wajib', 'level_kemahiran' => 'ahli'], // Digital Marketing
                    ['esco_skill_id' => 10173, 'tipe_keahlian' => 'wajib', 'level_kemahiran' => 'ahli'], // Advertising Campaigns
                    ['esco_skill_id' => 11879, 'tipe_keahlian' => 'tambahan', 'level_kemahiran' => 'menengah'], // Copywriting
                ],
            ],
            // 4. Frontend Web Engineer (Bandung)
            [
                'judul_lowongan' => 'Frontend React & Next.js Engineer',
                'nama_perusahaan' => 'PT Solusi Teknologi Bandung',
                'kbji_id' => 56, // 2512.03 - Pengembang Web Fullstack JavaScript
                'deskripsi_pekerjaan' => 'Mengembangkan antarmuka web modern dengan performa tinggi, responsif dan interaktif menggunakan React, Next.js, dan Tailwind CSS.',
                'tipe_pekerjaan' => 'Full-Time',
                'sistem_kerja' => 'Hybrid',
                'jumlah_kebutuhan' => 2,
                'education_level_id' => 5, // S1
                'jurusan_studi' => 'Informatika / Ilmu Komputer',
                'pengalaman_minimal_tahun' => 1,
                'usia_minimal' => 21,
                'usia_maksimal' => 30,
                'jenis_kelamin' => 'Semua',
                'is_disabilitas' => false,
                'persyaratan_tambahan' => 'Paham state management dan integrasi RESTful/GraphQL API.',
                'provinsi_id' => '32',
                'regency_id' => '32.73', // Kota Bandung
                'alamat_lengkap_penempatan' => 'Jl. Ir. H. Juanda No. 120, Dago, Kota Bandung',
                'gaji_tampilkan' => true,
                'gaji_minimal' => 8000000.00,
                'gaji_maksimal' => 13000000.00,
                'status_lowongan' => 'Published',
                'tanggal_buka' => now()->subDays(4),
                'tanggal_tutup' => now()->addDays(26),
                'skills' => [
                    ['esco_skill_id' => 3986, 'tipe_keahlian' => 'wajib', 'level_kemahiran' => 'ahli'], // Javascript
                    ['esco_skill_id' => 8034, 'tipe_keahlian' => 'wajib', 'level_kemahiran' => 'ahli'], // Typescript
                    ['esco_skill_id' => 3153, 'tipe_keahlian' => 'tambahan', 'level_kemahiran' => 'menengah'], // Visual Design
                ],
            ],
            // 5. DevOps & Cloud Engineer
            [
                'judul_lowongan' => 'Cloud & DevOps Infrastructure Specialist',
                'nama_perusahaan' => 'PT Nusantara Cloud Inovasi',
                'kbji_id' => 57, // 2519.01 - Spesialis DevOps & Cloud Infrastructure
                'deskripsi_pekerjaan' => 'Mengelola pipeline CI/CD, deployment container Kubernetes, infrastruktur cloud AWS/GCP, dan otomasi monitoring sistem.',
                'tipe_pekerjaan' => 'Full-Time',
                'sistem_kerja' => 'WFH',
                'jumlah_kebutuhan' => 2,
                'education_level_id' => 5, // S1
                'jurusan_studi' => 'Teknik Komputer / Informatika',
                'pengalaman_minimal_tahun' => 3,
                'usia_minimal' => 23,
                'usia_maksimal' => 35,
                'jenis_kelamin' => 'Semua',
                'is_disabilitas' => false,
                'persyaratan_tambahan' => 'Diutamakan memiliki sertifikasi CKA atau AWS Solution Architect.',
                'provinsi_id' => '31',
                'regency_id' => '31.74', // Jakarta Selatan
                'alamat_lengkap_penempatan' => 'TB Simatupang Building Lt 8, Cilandak, Jakarta Selatan',
                'gaji_tampilkan' => true,
                'gaji_minimal' => 16000000.00,
                'gaji_maksimal' => 25000000.00,
                'status_lowongan' => 'Published',
                'tanggal_buka' => now()->subDays(6),
                'tanggal_tutup' => now()->addDays(24),
                'skills' => [
                    ['esco_skill_id' => 13729, 'tipe_keahlian' => 'wajib', 'level_kemahiran' => 'ahli'], // DevOps
                    ['esco_skill_id' => 1182, 'tipe_keahlian' => 'wajib', 'level_kemahiran' => 'ahli'], // Cloud Migration
                    ['esco_skill_id' => 118, 'tipe_keahlian' => 'tambahan', 'level_kemahiran' => 'menengah'], // Network/DB Design
                ],
            ],
            // 6. Database Administrator (Surabaya)
            [
                'judul_lowongan' => 'Database Administrator (PostgreSQL & MySQL)',
                'nama_perusahaan' => 'PT Jawa Finansial Niaga',
                'kbji_id' => 60, // 2521.02 - Database Administrator
                'deskripsi_pekerjaan' => 'Memastikan availability, backup-recovery, query tuning, indexing, dan keamanan data transaksional perbankan & e-commerce.',
                'tipe_pekerjaan' => 'Full-Time',
                'sistem_kerja' => 'WFO',
                'jumlah_kebutuhan' => 1,
                'education_level_id' => 5, // S1
                'jurusan_studi' => 'Sistem Informasi / Informatika',
                'pengalaman_minimal_tahun' => 2,
                'usia_minimal' => 22,
                'usia_maksimal' => 35,
                'jenis_kelamin' => 'Semua',
                'is_disabilitas' => false,
                'persyaratan_tambahan' => 'Berpengalaman menangani database skala besar (high concurrency).',
                'provinsi_id' => '35',
                'regency_id' => '35.78', // Kota Surabaya
                'alamat_lengkap_penempatan' => 'Jl. Pemuda No. 45, Embong Kaliasin, Genteng, Kota Surabaya',
                'gaji_tampilkan' => true,
                'gaji_minimal' => 11000000.00,
                'gaji_maksimal' => 17000000.00,
                'status_lowongan' => 'Published',
                'tanggal_buka' => now()->subDays(7),
                'tanggal_tutup' => now()->addDays(20),
                'skills' => [
                    ['esco_skill_id' => 118, 'tipe_keahlian' => 'wajib', 'level_kemahiran' => 'ahli'], // Database Design & Admin
                    ['esco_skill_id' => 4882, 'tipe_keahlian' => 'wajib', 'level_kemahiran' => 'ahli'], // MySQL
                    ['esco_skill_id' => 7153, 'tipe_keahlian' => 'wajib', 'level_kemahiran' => 'ahli'], // Query Languages (SQL)
                ],
            ],
            // 7. UI/UX Product Designer (Yogyakarta)
            [
                'judul_lowongan' => 'UI/UX Product Designer',
                'nama_perusahaan' => 'CV Kreatif Jogja Multimedia',
                'kbji_id' => 38, // 2512 - Software Developers
                'deskripsi_pekerjaan' => 'Melakukan user research, wireframing, high-fidelity mockup di Figma, serta usability testing untuk produk digital SaaS.',
                'tipe_pekerjaan' => 'Full-Time',
                'sistem_kerja' => 'Hybrid',
                'jumlah_kebutuhan' => 1,
                'education_level_id' => 5, // S1
                'jurusan_studi' => 'DKV / Sistem Informasi / Desain Produk',
                'pengalaman_minimal_tahun' => 1,
                'usia_minimal' => 21,
                'usia_maksimal' => 30,
                'jenis_kelamin' => 'Semua',
                'is_disabilitas' => false,
                'persyaratan_tambahan' => 'Menyertakan link portofolio (Behance / Dribbble / Figma).',
                'provinsi_id' => '34',
                'regency_id' => '34.71', // Kota Yogyakarta
                'alamat_lengkap_penempatan' => 'Jl. Malioboro No. 56, Sosromenduran, Kota Yogyakarta',
                'gaji_tampilkan' => true,
                'gaji_minimal' => 6500000.00,
                'gaji_maksimal' => 10000000.00,
                'status_lowongan' => 'Published',
                'tanggal_buka' => now()->subDays(3),
                'tanggal_tutup' => now()->addDays(25),
                'skills' => [
                    ['esco_skill_id' => 7680, 'tipe_keahlian' => 'wajib', 'level_kemahiran' => 'ahli'], // Graphic Design
                    ['esco_skill_id' => 3153, 'tipe_keahlian' => 'wajib', 'level_kemahiran' => 'ahli'], // Visual Design
                    ['esco_skill_id' => 1758, 'tipe_keahlian' => 'tambahan', 'level_kemahiran' => 'menengah'], // Photoshop
                ],
            ],
            // 8. Junior Software Developer (Python)
            [
                'judul_lowongan' => 'Junior Software Engineer (Python & Data Pipeline)',
                'nama_perusahaan' => 'PT Depok Data Optima',
                'kbji_id' => 54, // 2512.01 - Pengembang Piranti Lunak
                'deskripsi_pekerjaan' => 'Membangun script otomasi crawling, pembersihan data, dan integrasi API microservice backend menggunakan Python.',
                'tipe_pekerjaan' => 'Full-Time',
                'sistem_kerja' => 'WFO',
                'jumlah_kebutuhan' => 2,
                'education_level_id' => 4, // D3
                'jurusan_studi' => 'Teknik Komputer / Informatika',
                'pengalaman_minimal_tahun' => 0,
                'usia_minimal' => 20,
                'usia_maksimal' => 28,
                'jenis_kelamin' => 'Semua',
                'is_disabilitas' => true,
                'persyaratan_tambahan' => 'Terbuka bagi fresh graduate yang aktif mengerjakan proyek open-source.',
                'provinsi_id' => '32',
                'regency_id' => '32.76', // Kota Depok
                'alamat_lengkap_penempatan' => 'Jl. Margonda Raya No. 100, Beji, Kota Depok',
                'gaji_tampilkan' => true,
                'gaji_minimal' => 5500000.00,
                'gaji_maksimal' => 8500000.00,
                'status_lowongan' => 'Published',
                'tanggal_buka' => now()->subDays(1),
                'tanggal_tutup' => now()->addDays(28),
                'skills' => [
                    ['esco_skill_id' => 11810, 'tipe_keahlian' => 'wajib', 'level_kemahiran' => 'menengah'], // Python
                    ['esco_skill_id' => 491, 'tipe_keahlian' => 'wajib', 'level_kemahiran' => 'menengah'], // Programming
                    ['esco_skill_id' => 7153, 'tipe_keahlian' => 'tambahan', 'level_kemahiran' => 'pemula'], // SQL
                ],
            ],
            // 9. Staf Administrasi Perkantoran
            [
                'judul_lowongan' => 'Staf Administrasi & Kesekretariatan Perkantoran',
                'nama_perusahaan' => 'PT Batavia Prima Investama',
                'kbji_id' => 67, // 4110.01 - Staf Administrasi Perkantoran
                'deskripsi_pekerjaan' => 'Mengelola surat masuk/keluar, filling dokumen fisik dan digital, menyusun agenda rapat pimpinan, serta rekap data operasional mingguan.',
                'tipe_pekerjaan' => 'Full-Time',
                'sistem_kerja' => 'WFO',
                'jumlah_kebutuhan' => 2,
                'education_level_id' => 4, // D3
                'jurusan_studi' => 'Administrasi Bisnis / Kesekretariatan / Manajemen',
                'pengalaman_minimal_tahun' => 1,
                'usia_minimal' => 20,
                'usia_maksimal' => 29,
                'jenis_kelamin' => 'Semua',
                'is_disabilitas' => false,
                'persyaratan_tambahan' => 'Mahir mengoperasikan Microsoft Office (Word, Excel, PowerPoint) dan Google Workspace.',
                'provinsi_id' => '31',
                'regency_id' => '31.71', // Jakarta Pusat
                'alamat_lengkap_penempatan' => 'Gedung Menara Thamrin Lt. 15, Menteng, Jakarta Pusat',
                'gaji_tampilkan' => true,
                'gaji_minimal' => 5200000.00,
                'gaji_maksimal' => 7500000.00,
                'status_lowongan' => 'Published',
                'tanggal_buka' => now()->subDays(5),
                'tanggal_tutup' => now()->addDays(25),
                'skills' => [
                    ['esco_skill_id' => 6499, 'tipe_keahlian' => 'wajib', 'level_kemahiran' => 'ahli'], // Office Administration
                    ['esco_skill_id' => 11206, 'tipe_keahlian' => 'wajib', 'level_kemahiran' => 'ahli'], // Document Management
                    ['esco_skill_id' => 1804, 'tipe_keahlian' => 'tambahan', 'level_kemahiran' => 'menengah'], // Customer Service
                ],
            ],
            // 10. Accounting & Tax Staff (Semarang)
            [
                'judul_lowongan' => 'Staff Akuntansi & Pelaporan Pajak (Brevet A/B)',
                'nama_perusahaan' => 'PT Manufaktur Samudra Semarang',
                'kbji_id' => 46, // 4110 - Administrasi Umum
                'deskripsi_pekerjaan' => 'Menyusun jurnal umum, buku besar, laporan laba rugi, rekonsiliasi bank, dan pelaporan SPT Masa PPh & PPN bulanan.',
                'tipe_pekerjaan' => 'Full-Time',
                'sistem_kerja' => 'WFO',
                'jumlah_kebutuhan' => 1,
                'education_level_id' => 5, // S1
                'jurusan_studi' => 'Akuntansi / Perpajakan',
                'pengalaman_minimal_tahun' => 2,
                'usia_minimal' => 22,
                'usia_maksimal' => 33,
                'jenis_kelamin' => 'Semua',
                'is_disabilitas' => false,
                'persyaratan_tambahan' => 'Memiliki sertifikat Brevet Pajak A & B menjadi nilai tambah utama.',
                'provinsi_id' => '33',
                'regency_id' => '33.74', // Kota Semarang
                'alamat_lengkap_penempatan' => 'Kawasan Industri Wijayakusuma Blok C-4, Tugu, Kota Semarang',
                'gaji_tampilkan' => true,
                'gaji_minimal' => 6000000.00,
                'gaji_maksimal' => 9000000.00,
                'status_lowongan' => 'Published',
                'tanggal_buka' => now()->subDays(2),
                'tanggal_tutup' => now()->addDays(21),
                'skills' => [
                    ['esco_skill_id' => 73, 'tipe_keahlian' => 'wajib', 'level_kemahiran' => 'ahli'], // Accounting & Tax
                    ['esco_skill_id' => 3304, 'tipe_keahlian' => 'wajib', 'level_kemahiran' => 'ahli'], // Bookkeeping
                    ['esco_skill_id' => 1098, 'tipe_keahlian' => 'tambahan', 'level_kemahiran' => 'menengah'], // Financial Records
                ],
            ],
            // 11. Content Writer & Copywriter (Bandung)
            [
                'judul_lowongan' => 'Creative Content Writer & Copywriter',
                'nama_perusahaan' => 'PT Parahyangan Media Interaktif',
                'kbji_id' => 37, // 2431 - Pemasaran
                'deskripsi_pekerjaan' => 'Menulis naskah konten kreatif untuk media sosial, artikel SEO website, script video TikTok/Reels, dan materi promosi email.',
                'tipe_pekerjaan' => 'Full-Time',
                'sistem_kerja' => 'Hybrid',
                'jumlah_kebutuhan' => 2,
                'education_level_id' => 5, // S1
                'jurusan_studi' => 'Sastra / Ilmu Komunikasi / Jurnalistik',
                'pengalaman_minimal_tahun' => 1,
                'usia_minimal' => 21,
                'usia_maksimal' => 29,
                'jenis_kelamin' => 'Semua',
                'is_disabilitas' => false,
                'persyaratan_tambahan' => 'Memiliki portofolio tulisan atau artikel yang pernah dipublikasikan.',
                'provinsi_id' => '32',
                'regency_id' => '32.73', // Kota Bandung
                'alamat_lengkap_penempatan' => 'Jl. R.E. Martadinata No. 88, Citarum, Kota Bandung',
                'gaji_tampilkan' => true,
                'gaji_minimal' => 5000000.00,
                'gaji_maksimal' => 7500000.00,
                'status_lowongan' => 'Published',
                'tanggal_buka' => now()->subDays(4),
                'tanggal_tutup' => now()->addDays(20),
                'skills' => [
                    ['esco_skill_id' => 11879, 'tipe_keahlian' => 'wajib', 'level_kemahiran' => 'ahli'], // Copywriting
                    ['esco_skill_id' => 254, 'tipe_keahlian' => 'wajib', 'level_kemahiran' => 'menengah'], // Creative Writing
                    ['esco_skill_id' => 4344, 'tipe_keahlian' => 'tambahan', 'level_kemahiran' => 'pemula'], // Digital Marketing
                ],
            ],
            // 12. Graphic Designer
            [
                'judul_lowongan' => 'Senior Graphic Designer & Brand Specialist',
                'nama_perusahaan' => 'PT Visual Kreasi Jakarta',
                'kbji_id' => 37, // 2431 - Periklanan
                'deskripsi_pekerjaan' => 'Membuat aset visual identitas merek, desain materi pemasaran digital & cetak, packaging produk, dan key visual campaign.',
                'tipe_pekerjaan' => 'Full-Time',
                'sistem_kerja' => 'WFO',
                'jumlah_kebutuhan' => 1,
                'education_level_id' => 4, // D3
                'jurusan_studi' => 'Desain Komunikasi Visual (DKV)',
                'pengalaman_minimal_tahun' => 2,
                'usia_minimal' => 22,
                'usia_maksimal' => 32,
                'jenis_kelamin' => 'Semua',
                'is_disabilitas' => false,
                'persyaratan_tambahan' => 'Menguasai Adobe Photoshop, Illustrator, dan InDesign secara mahir.',
                'provinsi_id' => '31',
                'regency_id' => '31.73', // Jakarta Barat
                'alamat_lengkap_penempatan' => 'Jl. Panjang No. 12, Kebon Jeruk, Jakarta Barat',
                'gaji_tampilkan' => true,
                'gaji_minimal' => 7500000.00,
                'gaji_maksimal' => 11000000.00,
                'status_lowongan' => 'Published',
                'tanggal_buka' => now()->subDays(6),
                'tanggal_tutup' => now()->addDays(22),
                'skills' => [
                    ['esco_skill_id' => 7680, 'tipe_keahlian' => 'wajib', 'level_kemahiran' => 'ahli'], // Graphic Design
                    ['esco_skill_id' => 1758, 'tipe_keahlian' => 'wajib', 'level_kemahiran' => 'ahli'], // Photoshop
                    ['esco_skill_id' => 3153, 'tipe_keahlian' => 'tambahan', 'level_kemahiran' => 'menengah'], // Visual Design
                ],
            ],
            // 13. Customer Service Representative
            [
                'judul_lowongan' => 'Customer Service & Contact Center Agent',
                'nama_perusahaan' => 'PT Sentra Solusi Kontak',
                'kbji_id' => 68, // 4222.01 - Representatif CS
                'deskripsi_pekerjaan' => 'Menangani pertanyaan, keluhan pelanggan secara ramah melalui telepon, live chat, dan email serta mencatat tiket di aplikasi CRM.',
                'tipe_pekerjaan' => 'Full-Time',
                'sistem_kerja' => 'WFO',
                'jumlah_kebutuhan' => 5,
                'education_level_id' => 3, // SMA/SMK
                'jurusan_studi' => 'Semua Jurusan',
                'pengalaman_minimal_tahun' => 0,
                'usia_minimal' => 19,
                'usia_maksimal' => 28,
                'jenis_kelamin' => 'Semua',
                'is_disabilitas' => false,
                'persyaratan_tambahan' => 'Memiliki kemampuan komunikasi verbal yang santun, artikulasi jelas, dan empati tinggi.',
                'provinsi_id' => '36',
                'regency_id' => '36.71', // Kota Tangerang
                'alamat_lengkap_penempatan' => 'Kawasan Komersial Tangerang City Mall Lt 3, Cikokol, Kota Tangerang',
                'gaji_tampilkan' => true,
                'gaji_minimal' => 4800000.00,
                'gaji_maksimal' => 6200000.00,
                'status_lowongan' => 'Published',
                'tanggal_buka' => now()->subDays(2),
                'tanggal_tutup' => now()->addDays(28),
                'skills' => [
                    ['esco_skill_id' => 1804, 'tipe_keahlian' => 'wajib', 'level_kemahiran' => 'ahli'], // Customer Service
                    ['esco_skill_id' => 9038, 'tipe_keahlian' => 'wajib', 'level_kemahiran' => 'menengah'], // CRM Software
                    ['esco_skill_id' => 11206, 'tipe_keahlian' => 'tambahan', 'level_kemahiran' => 'pemula'], // Document Management
                ],
            ],
            // 14. HR & Recruitment Specialist
            [
                'judul_lowongan' => 'HR Specialist & Talent Acquisition Officer',
                'nama_perusahaan' => 'PT Prima Daya Korpora',
                'kbji_id' => 35, // 1219 - Manajer Pelayanan Bisnis
                'deskripsi_pekerjaan' => 'Mengelola proses end-to-end rekrutmen karyawan, psikotes/interview, administrasi BPJS, onboarding, dan hubungan industrial.',
                'tipe_pekerjaan' => 'Full-Time',
                'sistem_kerja' => 'WFO',
                'jumlah_kebutuhan' => 1,
                'education_level_id' => 5, // S1
                'jurusan_studi' => 'Psikologi / Manajemen SDM / Hukum',
                'pengalaman_minimal_tahun' => 2,
                'usia_minimal' => 23,
                'usia_maksimal' => 33,
                'jenis_kelamin' => 'Semua',
                'is_disabilitas' => false,
                'persyaratan_tambahan' => 'Memahami UU Ketenagakerjaan dan administrasi kepersonaliaan.',
                'provinsi_id' => '31',
                'regency_id' => '31.75', // Jakarta Timur
                'alamat_lengkap_penempatan' => 'Jl. Pemuda Kav. 75, Rawamangun, Jakarta Timur',
                'gaji_tampilkan' => true,
                'gaji_minimal' => 8000000.00,
                'gaji_maksimal' => 12000000.00,
                'status_lowongan' => 'Published',
                'tanggal_buka' => now()->subDays(5),
                'tanggal_tutup' => now()->addDays(20),
                'skills' => [
                    ['esco_skill_id' => 280, 'tipe_keahlian' => 'wajib', 'level_kemahiran' => 'ahli'], // HR Management
                    ['esco_skill_id' => 1614, 'tipe_keahlian' => 'wajib', 'level_kemahiran' => 'ahli'], // Personnel Management Advice
                    ['esco_skill_id' => 6499, 'tipe_keahlian' => 'tambahan', 'level_kemahiran' => 'menengah'], // Office Administration
                ],
            ],
            // 15. Teknisi Listrik & Otomasi Industri (Bekasi)
            [
                'judul_lowongan' => 'Teknisi Elektrikal & Otomasi Industri (PLC)',
                'nama_perusahaan' => 'PT Manufaktur Otomasi Prima',
                'kbji_id' => 62, // 3114.01 - Teknisi Otomasi & PLC
                'deskripsi_pekerjaan' => 'Melakukan pemeliharaan preventif, instalasi panel listrik industri, troubleshooting inverter, dan pemrograman sistem otomasi mesin pabrik.',
                'tipe_pekerjaan' => 'Full-Time',
                'sistem_kerja' => 'WFO',
                'jumlah_kebutuhan' => 3,
                'education_level_id' => 4, // D3
                'jurusan_studi' => 'Teknik Elektro / Mekatronika / Listrik Industri',
                'pengalaman_minimal_tahun' => 2,
                'usia_minimal' => 21,
                'usia_maksimal' => 35,
                'jenis_kelamin' => 'Laki-laki',
                'is_disabilitas' => false,
                'persyaratan_tambahan' => 'Mampu membaca wiring diagram dan standar K3 kelistrikan.',
                'provinsi_id' => '32',
                'regency_id' => '32.75', // Kota Bekasi
                'alamat_lengkap_penempatan' => 'Kawasan Industri Medan Satria Blok B-12, Kota Bekasi',
                'gaji_tampilkan' => true,
                'gaji_minimal' => 6500000.00,
                'gaji_maksimal' => 9500000.00,
                'status_lowongan' => 'Published',
                'tanggal_buka' => now()->subDays(7),
                'tanggal_tutup' => now()->addDays(18),
                'skills' => [
                    ['esco_skill_id' => 4058, 'tipe_keahlian' => 'wajib', 'level_kemahiran' => 'ahli'], // Electrical Engineering
                    ['esco_skill_id' => 5574, 'tipe_keahlian' => 'wajib', 'level_kemahiran' => 'menengah'], // Technical Drawings
                    ['esco_skill_id' => 4210, 'tipe_keahlian' => 'tambahan', 'level_kemahiran' => 'menengah'], // Workplace Safety
                ],
            ],
            // 16. Drafter CAD (Tangerang Selatan)
            [
                'judul_lowongan' => 'Drafter CAD & 3D Building Modeler',
                'nama_perusahaan' => 'PT Graha Rancang Bangun',
                'kbji_id' => 63, // 3118.01 - Juru Gambar CAD
                'deskripsi_pekerjaan' => 'Membuat gambar kerja arsitektur, struktur dan MEP 2D/3D menggunakan AutoCAD dan Revit sesuai spesifikasi teknis konsultan.',
                'tipe_pekerjaan' => 'Full-Time',
                'sistem_kerja' => 'WFO',
                'jumlah_kebutuhan' => 2,
                'education_level_id' => 3, // SMK
                'jurusan_studi' => 'Teknik Bangunan / DPIB / Arsitektur',
                'pengalaman_minimal_tahun' => 1,
                'usia_minimal' => 19,
                'usia_maksimal' => 30,
                'jenis_kelamin' => 'Semua',
                'is_disabilitas' => false,
                'persyaratan_tambahan' => 'Melampirkan contoh gambar kerja portofolio (DWG / PDF).',
                'provinsi_id' => '36',
                'regency_id' => '36.74', // Kota Tangerang Selatan
                'alamat_lengkap_penempatan' => 'BSD City Sektor 1.2, Serpong, Kota Tangerang Selatan',
                'gaji_tampilkan' => true,
                'gaji_minimal' => 5000000.00,
                'gaji_maksimal' => 7500000.00,
                'status_lowongan' => 'Published',
                'tanggal_buka' => now()->subDays(4),
                'tanggal_tutup' => now()->addDays(21),
                'skills' => [
                    ['esco_skill_id' => 5574, 'tipe_keahlian' => 'wajib', 'level_kemahiran' => 'ahli'], // Technical Drawings
                    ['esco_skill_id' => 3153, 'tipe_keahlian' => 'tambahan', 'level_kemahiran' => 'menengah'], // Visual Design
                    ['esco_skill_id' => 6499, 'tipe_keahlian' => 'tambahan', 'level_kemahiran' => 'pemula'], // Office Admin
                ],
            ],
            // 17. Staf Gudang & Logistik (Jakarta Utara)
            [
                'judul_lowongan' => 'Staf Operasional Gudang & Manajemen Logistik',
                'nama_perusahaan' => 'PT Maritim Logistik Sentosa',
                'kbji_id' => 69, // 4321.01 - Staf Pergudangan & Logistik
                'deskripsi_pekerjaan' => 'Mengelola proses receiving, putaway, picking, packing, stock opname berkala, dan dokumentasi surat jalan pengiriman barang.',
                'tipe_pekerjaan' => 'Full-Time',
                'sistem_kerja' => 'WFO',
                'jumlah_kebutuhan' => 4,
                'education_level_id' => 3, // SMA/SMK
                'jurusan_studi' => 'Semua Jurusan / Logistik',
                'pengalaman_minimal_tahun' => 1,
                'usia_minimal' => 20,
                'usia_maksimal' => 32,
                'jenis_kelamin' => 'Laki-laki',
                'is_disabilitas' => false,
                'persyaratan_tambahan' => 'Bersedia bekerja shift dan memiliki ketelitian administrasi pergudangan.',
                'provinsi_id' => '31',
                'regency_id' => '31.72', // Jakarta Utara
                'alamat_lengkap_penempatan' => 'Kawasan Berikat Nusantara (KBN) Marunda, Cilincing, Jakarta Utara',
                'gaji_tampilkan' => true,
                'gaji_minimal' => 5100000.00,
                'gaji_maksimal' => 6800000.00,
                'status_lowongan' => 'Published',
                'tanggal_buka' => now()->subDays(3),
                'tanggal_tutup' => now()->addDays(24),
                'skills' => [
                    ['esco_skill_id' => 1077, 'tipe_keahlian' => 'wajib', 'level_kemahiran' => 'ahli'], // Warehouse Operations
                    ['esco_skill_id' => 5609, 'tipe_keahlian' => 'wajib', 'level_kemahiran' => 'ahli'], // Inventory Rules
                    ['esco_skill_id' => 10440, 'tipe_keahlian' => 'tambahan', 'level_kemahiran' => 'menengah'], // Stock Control
                ],
            ],
            // 18. Petugas K3 & Keselamatan Kerja
            [
                'judul_lowongan' => 'Ahli Keselamatan dan Kesehatan Kerja (K3) Proyek',
                'nama_perusahaan' => 'PT Konstruksi Cipta Karya',
                'kbji_id' => 64, // 3151.01 - Ahli K3
                'deskripsi_pekerjaan' => 'Melakukan inspeksi hazard keselamatan di area proyek, safety briefing harian, penyusunan HIRADC, dan laporan investigasi insiden.',
                'tipe_pekerjaan' => 'Kontrak',
                'sistem_kerja' => 'WFO',
                'jumlah_kebutuhan' => 1,
                'education_level_id' => 5, // S1
                'jurusan_studi' => 'Kesehatan Masyarakat / K3 / Teknik Lingkungan',
                'pengalaman_minimal_tahun' => 2,
                'usia_minimal' => 23,
                'usia_maksimal' => 35,
                'jenis_kelamin' => 'Semua',
                'is_disabilitas' => false,
                'persyaratan_tambahan' => 'Wajib memiliki sertifikat SKP Ahli K3 Umum aktif dari Kemnaker RI.',
                'provinsi_id' => '36',
                'regency_id' => '36.71', // Kota Tangerang
                'alamat_lengkap_penempatan' => 'Area Proyek Tol Bandara Soekarno-Hatta, Benda, Kota Tangerang',
                'gaji_tampilkan' => true,
                'gaji_minimal' => 7500000.00,
                'gaji_maksimal' => 11000000.00,
                'status_lowongan' => 'Published',
                'tanggal_buka' => now()->subDays(6),
                'tanggal_tutup' => now()->addDays(19),
                'skills' => [
                    ['esco_skill_id' => 205, 'tipe_keahlian' => 'wajib', 'level_kemahiran' => 'ahli'], // Occupational Health and Safety
                    ['esco_skill_id' => 4210, 'tipe_keahlian' => 'wajib', 'level_kemahiran' => 'ahli'], // Workplace Safety Standards
                    ['esco_skill_id' => 11206, 'tipe_keahlian' => 'tambahan', 'level_kemahiran' => 'menengah'], // Document Management
                ],
            ],
            // 19. Quality Control & Assurance (Karawang)
            [
                'judul_lowongan' => 'Staf Quality Control & Jaminan Mutu Produk',
                'nama_perusahaan' => 'PT Presisi Pres Part Indonesia',
                'kbji_id' => 65, // 3152.01 - Staf QC
                'deskripsi_pekerjaan' => 'Melakukan inspeksi visual dan pengukuran dimensi incoming raw material, in-process part, serta final check sebelum shipping.',
                'tipe_pekerjaan' => 'Full-Time',
                'sistem_kerja' => 'WFO',
                'jumlah_kebutuhan' => 2,
                'education_level_id' => 4, // D3
                'jurusan_studi' => 'Teknik Mesin / Teknik Industri',
                'pengalaman_minimal_tahun' => 1,
                'usia_minimal' => 20,
                'usia_maksimal' => 30,
                'jenis_kelamin' => 'Semua',
                'is_disabilitas' => false,
                'persyaratan_tambahan' => 'Mampu menggunakan alat ukur presisi (caliper, micrometer, CMM) dan membaca gambar teknik.',
                'provinsi_id' => '32',
                'regency_id' => '32.75', // Kota Bekasi
                'alamat_lengkap_penempatan' => 'Kawasan Industri GIIC Cikarang Pusat, Bekasi',
                'gaji_tampilkan' => true,
                'gaji_minimal' => 6000000.00,
                'gaji_maksimal' => 8500000.00,
                'status_lowongan' => 'Published',
                'tanggal_buka' => now()->subDays(5),
                'tanggal_tutup' => now()->addDays(20),
                'skills' => [
                    ['esco_skill_id' => 5574, 'tipe_keahlian' => 'wajib', 'level_kemahiran' => 'ahli'], // Technical Drawings
                    ['esco_skill_id' => 5609, 'tipe_keahlian' => 'wajib', 'level_kemahiran' => 'menengah'], // Inventory/Inspection Rules
                    ['esco_skill_id' => 4210, 'tipe_keahlian' => 'tambahan', 'level_kemahiran' => 'menengah'], // Workplace Safety
                ],
            ],
            // 20. Perawat Klinis Medis (Bogor)
            [
                'judul_lowongan' => 'Perawat Medis Klinis (Rawat Inap & IGD)',
                'nama_perusahaan' => 'RS Siloam Graha Medika',
                'kbji_id' => 52, // 2221.01 - Perawat Klinis Medis
                'deskripsi_pekerjaan' => 'Memberikan asuhan keperawatan langsung kepada pasien, pemberian obat sesuai resep dokter, monitoring tanda vital, dan dokumentasi medis.',
                'tipe_pekerjaan' => 'Full-Time',
                'sistem_kerja' => 'WFO',
                'jumlah_kebutuhan' => 4,
                'education_level_id' => 5, // S1 Profesi Ners
                'jurusan_studi' => 'Ilmu Keperawatan (Profesi Ners)',
                'pengalaman_minimal_tahun' => 1,
                'usia_minimal' => 22,
                'usia_maksimal' => 32,
                'jenis_kelamin' => 'Semua',
                'is_disabilitas' => false,
                'persyaratan_tambahan' => 'Memiliki STR aktif dan sertifikat BTCLS yang masih berlaku.',
                'provinsi_id' => '32',
                'regency_id' => '32.71', // Kota Bogor
                'alamat_lengkap_penempatan' => 'Jl. Raya Pajajaran No. 27, Babakan, Kota Bogor',
                'gaji_tampilkan' => true,
                'gaji_minimal' => 6500000.00,
                'gaji_maksimal' => 9000000.00,
                'status_lowongan' => 'Published',
                'tanggal_buka' => now()->subDays(4),
                'tanggal_tutup' => now()->addDays(24),
                'skills' => [
                    ['esco_skill_id' => 406, 'tipe_keahlian' => 'wajib', 'level_kemahiran' => 'ahli'], // Medical/Nursing Care
                    ['esco_skill_id' => 2793, 'tipe_keahlian' => 'wajib', 'level_kemahiran' => 'menengah'], // Patient Care Team
                    ['esco_skill_id' => 205, 'tipe_keahlian' => 'tambahan', 'level_kemahiran' => 'menengah'], // Health & Safety
                ],
            ],
            // 21. Supervisor Restoran & Kafe (Bali)
            [
                'judul_lowongan' => 'Restaurant & Cafe Operational Supervisor',
                'nama_perusahaan' => 'PT Bali Boga Culinary Group',
                'kbji_id' => 70, // 5151.01 - Supervisor Restoran
                'deskripsi_pekerjaan' => 'Mengawasi kelancaran operasional harian outlet resto, standar hospitality front of house, efisiensi inventory bahan baku, dan kepuasan tamu.',
                'tipe_pekerjaan' => 'Full-Time',
                'sistem_kerja' => 'WFO',
                'jumlah_kebutuhan' => 2,
                'education_level_id' => 4, // D3
                'jurusan_studi' => 'Manajemen Perhotelan / Pariwisata',
                'pengalaman_minimal_tahun' => 2,
                'usia_minimal' => 22,
                'usia_maksimal' => 35,
                'jenis_kelamin' => 'Semua',
                'is_disabilitas' => false,
                'persyaratan_tambahan' => 'Mampu berbahasa Inggris lisan secara lancar untuk melayani wisatawan mancanegara.',
                'provinsi_id' => '51',
                'regency_id' => '51.71', // Kota Denpasar
                'alamat_lengkap_penempatan' => 'Jl. Danau Tamblingan No. 42, Sanur, Kota Denpasar',
                'gaji_tampilkan' => true,
                'gaji_minimal' => 6000000.00,
                'gaji_maksimal' => 9500000.00,
                'status_lowongan' => 'Published',
                'tanggal_buka' => now()->subDays(3),
                'tanggal_tutup' => now()->addDays(25),
                'skills' => [
                    ['esco_skill_id' => 10907, 'tipe_keahlian' => 'wajib', 'level_kemahiran' => 'ahli'], // Food Service Operations
                    ['esco_skill_id' => 1804, 'tipe_keahlian' => 'wajib', 'level_kemahiran' => 'ahli'], // Customer Service
                    ['esco_skill_id' => 5609, 'tipe_keahlian' => 'tambahan', 'level_kemahiran' => 'menengah'], // Inventory Management
                ],
            ],
            // 22. Sales Executive B2B
            [
                'judul_lowongan' => 'B2B Corporate Sales & Account Executive',
                'nama_perusahaan' => 'PT Nusantara Niaga Solusindo',
                'kbji_id' => 66, // 3322.01 - Eksekutif Penjualan Komersial
                'deskripsi_pekerjaan' => 'Mencari prospek klien korporasi baru, mempresentasikan penawaran solusi produk, negosiasi kontrak, dan mencapai target penjualan kuartalan.',
                'tipe_pekerjaan' => 'Full-Time',
                'sistem_kerja' => 'Hybrid',
                'jumlah_kebutuhan' => 3,
                'education_level_id' => 5, // S1
                'jurusan_studi' => 'Manajemen / Komunikasi / Bisnis',
                'pengalaman_minimal_tahun' => 2,
                'usia_minimal' => 22,
                'usia_maksimal' => 34,
                'jenis_kelamin' => 'Semua',
                'is_disabilitas' => false,
                'persyaratan_tambahan' => 'Memiliki relasi korporasi yang luas dan keterampilan negosiasi persuasif.',
                'provinsi_id' => '31',
                'regency_id' => '31.74', // Jakarta Selatan
                'alamat_lengkap_penempatan' => 'Sudirman Central Business District (SCBD) Lot 9, Senayan, Jakarta Selatan',
                'gaji_tampilkan' => true,
                'gaji_minimal' => 8000000.00,
                'gaji_maksimal' => 15000000.00,
                'status_lowongan' => 'Published',
                'tanggal_buka' => now()->subDays(4),
                'tanggal_tutup' => now()->addDays(26),
                'skills' => [
                    ['esco_skill_id' => 9038, 'tipe_keahlian' => 'wajib', 'level_kemahiran' => 'ahli'], // CRM Software
                    ['esco_skill_id' => 1804, 'tipe_keahlian' => 'wajib', 'level_kemahiran' => 'ahli'], // Customer Service
                    ['esco_skill_id' => 10173, 'tipe_keahlian' => 'tambahan', 'level_kemahiran' => 'menengah'], // Advertising/Campaigns
                ],
            ],
        ];

        foreach ($vacancies as $item) {
            $skills = $item['skills'];
            unset($item['skills']);

            $slug = Str::slug($item['judul_lowongan'] . '-' . Str::random(5));
            $item['slug'] = $slug;

            // Check if same job title already exists in company
            $lowongan = LowonganKerja::updateOrCreate(
                [
                    'judul_lowongan' => $item['judul_lowongan'],
                    'nama_perusahaan' => $item['nama_perusahaan'],
                ],
                $item
            );

            // Attach required skills
            $skillSync = [];
            foreach ($skills as $sk) {
                // Verify skill exists in database
                if (SkillNode::where('id', $sk['esco_skill_id'])->exists()) {
                    $skillSync[$sk['esco_skill_id']] = [
                        'tipe_keahlian' => $sk['tipe_keahlian'],
                        'level_kemahiran' => $sk['level_kemahiran'],
                    ];
                }
            }

            $lowongan->skills()->sync($skillSync);
        }

        $this->command->info('LowonganKerjaSeeder: ' . count($vacancies) . ' formasi lowongan berhasil di-seed.');
    }
}
