<?php

namespace Database\Seeders;

use App\Models\JobSeeker;
use App\Models\SkillNode;
use Illuminate\Database\Seeder;

class JobSeekerSeeder extends Seeder
{
    public function run(): void
    {
        $seekers = [
            [
                'nik' => '3171011002950001',
                'full_name' => 'Ahmad Fauzan',
                'phone' => '081298765001',
                'birth_date' => '1995-02-10',
                'gender' => 'L',
                'regency_id' => '31.71', // Jakarta Pusat
                'education_level_id' => 5, // S1
                'study_field_group' => 'Teknologi Informasi & Komunikasi (TIK)',
                'study_field_detail' => 'Teknik Informatika',
                'experience_range' => '3-5',
                'desired_occupation' => 'Fullstack Web Developer',
                'kbji_id' => 56, // 2512.03 - Pengembang Web Fullstack JavaScript
                'trainings' => [
                    ['name' => 'Fullstack Web Engineering', 'organizer' => 'Hacktiv8', 'year' => 2021],
                ],
                'certifications' => [
                    ['name' => 'AWS Certified Developer', 'type' => 'Internasional', 'year' => 2023],
                ],
                'skills' => [3986, 8034, 118, 491, 119],
            ],
            [
                'nik' => '3174022405970002',
                'full_name' => 'Siti Nurhaliza',
                'phone' => '081387654002',
                'birth_date' => '1997-05-24',
                'gender' => 'P',
                'regency_id' => '31.74', // Jakarta Selatan
                'education_level_id' => 5, // S1
                'study_field_group' => 'Sains, Matematika, & Statistika',
                'study_field_detail' => 'Statistika Terapan',
                'experience_range' => '1-3',
                'desired_occupation' => 'Data Analyst',
                'kbji_id' => 59, // 2521.01 - Analis Data & Pemodel Statistik
                'trainings' => [
                    ['name' => 'Data Science Bootcamp', 'organizer' => 'DQLab', 'year' => 2023],
                ],
                'certifications' => [
                    ['name' => 'Google Data Analytics Professional', 'type' => 'Internasional', 'year' => 2024],
                ],
                'skills' => [11810, 3735, 2700, 7153, 277],
            ],
            [
                'nik' => '3273031508980003',
                'full_name' => 'Rian Pratama',
                'phone' => '082112345003',
                'birth_date' => '1998-08-15',
                'gender' => 'L',
                'regency_id' => '32.73', // Kota Bandung
                'education_level_id' => 5, // S1
                'study_field_group' => 'Teknologi Informasi & Komunikasi (TIK)',
                'study_field_detail' => 'Sistem Informasi',
                'experience_range' => '1-3',
                'desired_occupation' => 'Frontend Developer',
                'kbji_id' => 56, // 2512.03 - Pengembang Web Fullstack JavaScript
                'trainings' => [
                    ['name' => 'React & Next.js Advanced', 'organizer' => 'Binar Academy', 'year' => 2023],
                ],
                'certifications' => [
                    ['name' => 'Meta Front-End Developer', 'type' => 'Internasional', 'year' => 2024],
                ],
                'skills' => [3986, 8034, 3153, 491],
            ],
            [
                'nik' => '3171041903960004',
                'full_name' => 'Dina Wulandari',
                'phone' => '081234567004',
                'birth_date' => '1996-03-19',
                'gender' => 'P',
                'regency_id' => '31.71', // Jakarta Pusat
                'education_level_id' => 5, // S1
                'study_field_group' => 'Ilmu Sosial, Bisnis, & Hukum',
                'study_field_detail' => 'Ilmu Komunikasi & Hubungan Masyarakat',
                'experience_range' => '3-5',
                'desired_occupation' => 'Digital Marketing Specialist',
                'kbji_id' => 53, // 2431.01 - Spesialis Pemasaran Digital
                'trainings' => [
                    ['name' => 'Performance Marketing & SEO', 'organizer' => 'RevoU', 'year' => 2022],
                ],
                'certifications' => [
                    ['name' => 'Google Ads Search Certification', 'type' => 'Internasional', 'year' => 2023],
                ],
                'skills' => [4344, 10173, 11879, 3031],
            ],
            [
                'nik' => '3174051211940005',
                'full_name' => 'Muhammad Rizky',
                'phone' => '085698761005',
                'birth_date' => '1994-11-12',
                'gender' => 'L',
                'regency_id' => '31.74', // Jakarta Selatan
                'education_level_id' => 5, // S1
                'study_field_group' => 'Teknologi Informasi & Komunikasi (TIK)',
                'study_field_detail' => 'Teknik Komputer',
                'experience_range' => '>5',
                'desired_occupation' => 'DevOps Engineer',
                'kbji_id' => 57, // 2519.01 - Spesialis DevOps & Cloud Infrastructure
                'trainings' => [
                    ['name' => 'Cloud Architecture Masterclass', 'organizer' => 'Purwadhika', 'year' => 2020],
                ],
                'certifications' => [
                    ['name' => 'Certified Kubernetes Administrator (CKA)', 'type' => 'Internasional', 'year' => 2023],
                ],
                'skills' => [13729, 1182, 118, 11810],
            ],
            [
                'nik' => '3578062804970006',
                'full_name' => 'Eka Putri Lestari',
                'phone' => '087712349006',
                'birth_date' => '1997-04-28',
                'gender' => 'P',
                'regency_id' => '35.78', // Kota Surabaya
                'education_level_id' => 5, // S1
                'study_field_group' => 'Teknologi Informasi & Komunikasi (TIK)',
                'study_field_detail' => 'Manajemen Informatika',
                'experience_range' => '1-3',
                'desired_occupation' => 'Database Administrator',
                'kbji_id' => 60, // 2521.02 - Database Administrator
                'trainings' => [
                    ['name' => 'PostgreSQL Database Administration', 'organizer' => 'Brainmatics', 'year' => 2023],
                ],
                'certifications' => [
                    ['name' => 'Oracle Database Certified Associate', 'type' => 'Internasional', 'year' => 2024],
                ],
                'skills' => [118, 4882, 7153, 290],
            ],
            [
                'nik' => '3471071409990007',
                'full_name' => 'Fajar Hidayat',
                'phone' => '089612345007',
                'birth_date' => '1999-09-14',
                'gender' => 'L',
                'regency_id' => '34.71', // Kota Yogyakarta
                'education_level_id' => 5, // S1
                'study_field_group' => 'Humaniora & Seni',
                'study_field_detail' => 'Desain Komunikasi Visual',
                'experience_range' => 'fresh_graduate',
                'desired_occupation' => 'UI/UX Designer',
                'kbji_id' => 38, // 2512 - Software Developers / Designer
                'trainings' => [
                    ['name' => 'UI/UX Product Design Intensive', 'organizer' => 'Skilvul', 'year' => 2024],
                ],
                'certifications' => [
                    ['name' => 'Figma UI/UX Specialist', 'type' => 'Nasional', 'year' => 2024],
                ],
                'skills' => [7680, 1758, 3153],
            ],
            [
                'nik' => '3276082201010008',
                'full_name' => 'Gilang Ramadhan',
                'phone' => '081399887008',
                'birth_date' => '2001-01-22',
                'gender' => 'L',
                'regency_id' => '32.76', // Kota Depok
                'education_level_id' => 4, // D3
                'study_field_group' => 'Teknologi Informasi & Komunikasi (TIK)',
                'study_field_detail' => 'Teknik Komputer',
                'experience_range' => 'fresh_graduate',
                'desired_occupation' => 'Junior Software Engineer',
                'kbji_id' => 54, // 2512.01 - Pengembang Piranti Lunak
                'trainings' => [
                    ['name' => 'Pelatihan Pemrograman Python Dasar', 'organizer' => 'BBPVP Bekasi Kemnaker', 'year' => 2024],
                ],
                'certifications' => [
                    ['name' => 'Sertifikasi BNSP Junior Web Developer', 'type' => 'Nasional', 'year' => 2024],
                ],
                'skills' => [11810, 491, 7153],
            ],
            [
                'nik' => '3171091107980009',
                'full_name' => 'Annisa Rahmawati',
                'phone' => '085712349009',
                'birth_date' => '1998-07-11',
                'gender' => 'P',
                'regency_id' => '31.71', // Jakarta Pusat
                'education_level_id' => 4, // D3
                'study_field_group' => 'Ilmu Sosial, Bisnis, & Hukum',
                'study_field_detail' => 'Administrasi Bisnis / Perkantoran',
                'experience_range' => '1-3',
                'desired_occupation' => 'Staf Administrasi Perkantoran',
                'kbji_id' => 67, // 4110.01 - Staf Administrasi Perkantoran
                'trainings' => [
                    ['name' => 'Administrasi Perkantoran Modern & Kearsipan', 'organizer' => 'Kemnaker BBPVP Serang', 'year' => 2022],
                ],
                'certifications' => [
                    ['name' => 'BNSP Tenaga Administrasi Perkantoran', 'type' => 'Nasional', 'year' => 2023],
                ],
                'skills' => [6499, 11206, 1804],
            ],
            [
                'nik' => '3374100512960010',
                'full_name' => 'Bambang Wijaya',
                'phone' => '081288776010',
                'birth_date' => '1996-12-05',
                'gender' => 'L',
                'regency_id' => '33.74', // Kota Semarang
                'education_level_id' => 5, // S1
                'study_field_group' => 'Ilmu Sosial, Bisnis, & Hukum',
                'study_field_detail' => 'Akuntansi Keuangan',
                'experience_range' => '3-5',
                'desired_occupation' => 'Accounting & Tax Specialist',
                'kbji_id' => 46, // 4110 - Administrasi Umum
                'trainings' => [
                    ['name' => 'Brevet Pajak A & B Terpadu', 'organizer' => 'Ikatan Akuntan Indonesia', 'year' => 2021],
                ],
                'certifications' => [
                    ['name' => 'Certified Tax Technician (CTT)', 'type' => 'Nasional', 'year' => 2023],
                ],
                'skills' => [73, 3304, 1098],
            ],
            [
                'nik' => '3273111706990011',
                'full_name' => 'Citra Dewi',
                'phone' => '082212349011',
                'birth_date' => '1999-06-17',
                'gender' => 'P',
                'regency_id' => '32.73', // Kota Bandung
                'education_level_id' => 5, // S1
                'study_field_group' => 'Humaniora & Seni',
                'study_field_detail' => 'Sastra Indonesia & Jurnalistik',
                'experience_range' => '1-3',
                'desired_occupation' => 'Content Writer & Copywriter',
                'kbji_id' => 37, // 2431 - Periklanan dan Pemasaran
                'trainings' => [
                    ['name' => 'Creative & Advertising Copywriting', 'organizer' => 'Tempo Institute', 'year' => 2023],
                ],
                'certifications' => [
                    ['name' => 'BNSP Penulis Konten Kreatif', 'type' => 'Nasional', 'year' => 2024],
                ],
                'skills' => [11879, 254, 551, 4344],
            ],
            [
                'nik' => '3173122903970012',
                'full_name' => 'Dimas Anggara',
                'phone' => '081312347012',
                'birth_date' => '1997-03-29',
                'gender' => 'L',
                'regency_id' => '31.73', // Jakarta Barat
                'education_level_id' => 4, // D3
                'study_field_group' => 'Humaniora & Seni',
                'study_field_detail' => 'Desain Grafis Multimedia',
                'experience_range' => '1-3',
                'desired_occupation' => 'Graphic Designer',
                'kbji_id' => 37, // 2431 - Periklanan dan Pemasaran
                'trainings' => [
                    ['name' => 'Branding & Social Media Visuals', 'organizer' => 'Creative Hub Jakarta', 'year' => 2023],
                ],
                'certifications' => [
                    ['name' => 'Adobe Certified Professional in Graphic Design', 'type' => 'Internasional', 'year' => 2023],
                ],
                'skills' => [7680, 1758, 3153],
            ],
            [
                'nik' => '3671131010000013',
                'full_name' => 'Fitri Handayani',
                'phone' => '087812346013',
                'birth_date' => '2000-10-10',
                'gender' => 'P',
                'regency_id' => '36.71', // Kota Tangerang
                'education_level_id' => 3, // SMA/SMK
                'study_field_group' => 'Umum (SD / SMP / SMA)',
                'study_field_detail' => 'SMK Bisnis Manajemen',
                'experience_range' => '1-3',
                'desired_occupation' => 'Customer Care & Call Center',
                'kbji_id' => 68, // 4222.01 - Representatif Layanan Pelanggan
                'trainings' => [
                    ['name' => 'Service Excellence & Contact Center', 'organizer' => 'Indonesia Contact Center Association', 'year' => 2022],
                ],
                'certifications' => [
                    ['name' => 'BNSP Customer Service Representative', 'type' => 'Nasional', 'year' => 2023],
                ],
                'skills' => [1804, 9038, 11206],
            ],
            [
                'nik' => '3175142104950014',
                'full_name' => 'Guruh Permana',
                'phone' => '081299881014',
                'birth_date' => '1995-04-21',
                'gender' => 'L',
                'regency_id' => '31.75', // Jakarta Timur
                'education_level_id' => 5, // S1
                'study_field_group' => 'Ilmu Sosial, Bisnis, & Hukum',
                'study_field_detail' => 'Psikologi Industri & Organisasi',
                'experience_range' => '3-5',
                'desired_occupation' => 'HR & Talent Acquisition Officer',
                'kbji_id' => 35, // 1219 - Manajer Pelayanan Bisnis
                'trainings' => [
                    ['name' => 'Strategic Human Resource Management', 'organizer' => 'PPM Manajemen', 'year' => 2021],
                ],
                'certifications' => [
                    ['name' => 'BNSP Staf Sumber Daya Manusia (SDM)', 'type' => 'Nasional', 'year' => 2023],
                ],
                'skills' => [280, 1614, 6499],
            ],
            [
                'nik' => '3275150808970015',
                'full_name' => 'Hendra Kusuma',
                'phone' => '085212349015',
                'birth_date' => '1997-08-08',
                'gender' => 'L',
                'regency_id' => '32.75', // Kota Bekasi
                'education_level_id' => 4, // D3
                'study_field_group' => 'Teknik, Manufaktur, & Konstruksi',
                'study_field_detail' => 'Teknik Elektro Industri',
                'experience_range' => '3-5',
                'desired_occupation' => 'Teknisi Otomasi & Listrik',
                'kbji_id' => 62, // 3114.01 - Teknisi Otomasi Industri & PLC
                'trainings' => [
                    ['name' => 'Pelatihan PLC & Kelistrikan Industri', 'organizer' => 'BBPVP Bekasi Kemnaker', 'year' => 2021],
                ],
                'certifications' => [
                    ['name' => 'Sertifikat Kompetensi Otomasi Industri BNSP', 'type' => 'Nasional', 'year' => 2022],
                ],
                'skills' => [4058, 5574, 4210],
            ],
            [
                'nik' => '3674161802990016',
                'full_name' => 'Indah Permatasari',
                'phone' => '081398765016',
                'birth_date' => '1999-02-18',
                'gender' => 'P',
                'regency_id' => '36.74', // Kota Tangerang Selatan
                'education_level_id' => 3, // SMK
                'study_field_group' => 'Teknik, Manufaktur, & Konstruksi',
                'study_field_detail' => 'Desain Pemodelan & Informasi Bangunan (DPIB)',
                'experience_range' => '1-3',
                'desired_occupation' => 'Drafter CAD & 3D Modeling',
                'kbji_id' => 63, // 3118.01 - Juru Gambar CAD
                'trainings' => [
                    ['name' => 'AutoCAD & Building Information Modeling (BIM)', 'organizer' => 'Autodesk Training Center', 'year' => 2023],
                ],
                'certifications' => [
                    ['name' => 'Autodesk Certified Professional AutoCAD', 'type' => 'Internasional', 'year' => 2024],
                ],
                'skills' => [5574, 3153, 6499],
            ],
            [
                'nik' => '3172171405980017',
                'full_name' => 'Joko Susilo',
                'phone' => '081288992017',
                'birth_date' => '1998-05-14',
                'gender' => 'L',
                'regency_id' => '31.72', // Jakarta Utara
                'education_level_id' => 3, // SMK
                'study_field_group' => 'Teknik, Manufaktur, & Konstruksi',
                'study_field_detail' => 'Teknik Logistik Pergudangan',
                'experience_range' => '1-3',
                'desired_occupation' => 'Staf Pergudangan & Inventory',
                'kbji_id' => 69, // 4321.01 - Staf Pergudangan dan Logistik
                'trainings' => [
                    ['name' => 'Manajemen Gudang & Operator Forklift', 'organizer' => 'BBPVP Serang Kemnaker', 'year' => 2022],
                ],
                'certifications' => [
                    ['name' => 'SIO Forklift Kemnaker RI', 'type' => 'Nasional', 'year' => 2023],
                ],
                'skills' => [1077, 5609, 10440],
            ],
            [
                'nik' => '3671182309960018',
                'full_name' => 'Kartika Sari',
                'phone' => '085712998018',
                'birth_date' => '1996-09-23',
                'gender' => 'P',
                'regency_id' => '36.71', // Kota Tangerang
                'education_level_id' => 5, // S1
                'study_field_group' => 'Kesehatan & Kesejahteraan Sosial',
                'study_field_detail' => 'Kesehatan Masyarakat (K3)',
                'experience_range' => '3-5',
                'desired_occupation' => 'Ahli Keselamatan Kerja (HSE Officer)',
                'kbji_id' => 64, // 3151.01 - Ahli K3
                'trainings' => [
                    ['name' => 'Pembinaan Calon Ahli K3 Umum', 'organizer' => 'PT Safe Tra Mandiri Kemnaker', 'year' => 2021],
                ],
                'certifications' => [
                    ['name' => 'Sertifikat Ahli K3 Umum Kemnaker RI', 'type' => 'Nasional', 'year' => 2022],
                ],
                'skills' => [205, 4210, 11206],
            ],
            [
                'nik' => '3275191206970019',
                'full_name' => 'Lukman Hakim',
                'phone' => '081322334019',
                'birth_date' => '1997-06-12',
                'gender' => 'L',
                'regency_id' => '32.75', // Kota Bekasi
                'education_level_id' => 4, // D3
                'study_field_group' => 'Teknik, Manufaktur, & Konstruksi',
                'study_field_detail' => 'Teknik Mesin Manufaktur',
                'experience_range' => '1-3',
                'desired_occupation' => 'Quality Control Inspector',
                'kbji_id' => 65, // 3152.01 - Staf Pengendalian Mutu
                'trainings' => [
                    ['name' => 'Pengendalian Kualitas Statistik (SPC) & Six Sigma', 'organizer' => 'Politeknik Manufaktur', 'year' => 2023],
                ],
                'certifications' => [
                    ['name' => 'Six Sigma Yellow Belt', 'type' => 'Internasional', 'year' => 2023],
                ],
                'skills' => [5574, 5609, 4210],
            ],
            [
                'nik' => '3271203004950020',
                'full_name' => 'Maya Anggraini',
                'phone' => '081277665020',
                'birth_date' => '1995-04-30',
                'gender' => 'P',
                'regency_id' => '32.71', // Kota Bogor
                'education_level_id' => 5, // S1 Ners
                'study_field_group' => 'Kesehatan & Kesejahteraan Sosial',
                'study_field_detail' => 'Ilmu Keperawatan (Profesi Ners)',
                'experience_range' => '3-5',
                'desired_occupation' => 'Perawat Medis Klinis',
                'kbji_id' => 52, // 2221.01 - Perawat Klinis Medis
                'trainings' => [
                    ['name' => 'Basic Trauma Cardiac Life Support (BTCLS)', 'organizer' => 'Persatuan Perawat Nasional Indonesia (PPNI)', 'year' => 2022],
                ],
                'certifications' => [
                    ['name' => 'Surat Tanda Registrasi (STR) Perawat Aktif', 'type' => 'Nasional', 'year' => 2023],
                ],
                'skills' => [406, 2793, 205],
            ],
            [
                'nik' => '5171211112980021',
                'full_name' => 'Naufal Akbar',
                'phone' => '087812991021',
                'birth_date' => '1998-12-11',
                'gender' => 'L',
                'regency_id' => '51.71', // Kota Denpasar
                'education_level_id' => 4, // D3
                'study_field_group' => 'Pariwisata, Perhotelan, & Jasa',
                'study_field_detail' => 'Manajemen Perhotelan & Restoran',
                'experience_range' => '1-3',
                'desired_occupation' => 'Supervisor Restoran & Kafe',
                'kbji_id' => 70, // 5151.01 - Supervisor Restoran
                'trainings' => [
                    ['name' => 'Food & Beverage Service Leadership', 'organizer' => 'Politeknik Pariwisata Bali', 'year' => 2023],
                ],
                'certifications' => [
                    ['name' => 'BNSP Supervisor Restoran', 'type' => 'Nasional', 'year' => 2024],
                ],
                'skills' => [10907, 1804, 5609],
            ],
            [
                'nik' => '3174220503960022',
                'full_name' => 'Pratama Yudha',
                'phone' => '081244556022',
                'birth_date' => '1996-03-05',
                'gender' => 'L',
                'regency_id' => '31.74', // Jakarta Selatan
                'education_level_id' => 5, // S1
                'study_field_group' => 'Ilmu Sosial, Bisnis, & Hukum',
                'study_field_detail' => 'Manajemen Pemasaran',
                'experience_range' => '3-5',
                'desired_occupation' => 'B2B Corporate Sales Representative',
                'kbji_id' => 66, // 3322.01 - Eksekutif Penjualan Komersial
                'trainings' => [
                    ['name' => 'Consultative Selling & Negotiation Skills', 'organizer' => 'MarkPlus Institute', 'year' => 2022],
                ],
                'certifications' => [
                    ['name' => 'Certified Sales Professional (CSP)', 'type' => 'Internasional', 'year' => 2023],
                ],
                'skills' => [9038, 1804, 10173, 4344],
            ],
        ];

        foreach ($seekers as $data) {
            $skills = $data['skills'];
            unset($data['skills']);

            $seeker = JobSeeker::updateOrCreate(
                ['nik' => $data['nik']],
                $data
            );

            // Sync skills
            $validSkills = SkillNode::whereIn('id', $skills)->pluck('id')->toArray();
            $seeker->skills()->sync($validSkills);
        }

        $this->command->info('JobSeekerSeeder: ' . count($seekers) . ' pencari kerja berhasil di-seed.');
    }
}
