<?php

namespace App\Services;

use App\Models\JobSeeker;
use App\Models\LowonganKerja;
use App\Models\KbjiClassification;
use Illuminate\Support\Facades\DB;

class MatchingEngineService
{
    /**
     * Stopwords bilingual (Bahasa Indonesia & Bahasa Inggris) untuk normalisasi teks
     */
    protected array $stopWords = [
        // Stopwords Bahasa Indonesia
        'dan', 'atau', 'di', 'ke', 'dari', 'yang', 'untuk', 'pada', 'dengan', 'adalah',
        'sebagai', 'seorang', 'staf', 'staff', 'karyawan', 'pegawai', 'pekerja',
        'magang', 'intern', 'internship', 'kerja', 'praktek', 'pkl', 'kursus',
        'pelatihan', 'sertifikat', 'sertifikasi', 'mampu', 'dapat', 'bisa', 'ahli',
        'terbiasa', 'menguasai', 'memahami', 'aplikasi', 'software', 'program',
        'alat', 'bidang', 'bagian', 'divisi', 'dll', 'dsb', 'dst', 'baik', 'benar',
        'secara', 'tingkat', 'level', 'minimal', 'maksimal', 'tahun', 'pengalaman',
        'pria', 'wanita', 'usia', 'pendidikan', 'jurusan', 'lulusan', 'tamatan',
        'kami', 'kita', 'anda', 'kamu', 'mereka', 'ia', 'dia', 'ini', 'itu',
        'tersebut', 'setiap', 'semua', 'oleh', 'tentang', 'serta', 'juga', 'saat',
        'ketika', 'selama', 'akan', 'telah', 'sudah', 'hanya', 'saja',
        // Stopwords Bahasa Inggris (mencegah kata sambung seperti 'and', 'the' dijadikan padanan)
        'and', 'or', 'the', 'a', 'an', 'in', 'on', 'at', 'to', 'for', 'of', 'with',
        'by', 'from', 'as', 'is', 'are', 'was', 'were', 'be', 'been', 'being',
        'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'shall', 'should',
        'can', 'could', 'may', 'might', 'must', 'this', 'that', 'these', 'those',
        'it', 'its', 'their', 'they', 'them', 'our', 'we', 'us', 'you', 'your',
        'he', 'she', 'him', 'her', 'his', 'such', 'all', 'any', 'both', 'each',
        'more', 'most', 'other', 'some', 'than', 'too', 'very', 'into', 'over',
        'under', 'again', 'further', 'then', 'once', 'here', 'there', 'when',
        'where', 'why', 'how', 'about', 'between', 'through', 'during', 'before',
        'after', 'above', 'below', 'up', 'down', 'out', 'off', 'same', 'so',
        'student', 'students', 'pupil', 'pupils', 'lesson', 'lessons', 'session',
        'sessions', 'event', 'events', 'program', 'programs', 'school', 'class', 'classes'
    ];

    /**
     * Klaster padanan sinonim & konsep keahlian industri (Bilingual ID/EN & Bebas Taksonomi)
     * Mencakup 52 domain industri & profesi ketenagakerjaan Indonesia secara presisi dengan frasa spesifik
     */
    protected array $skillSynonymClusters = [
        // 1. Sales & Pemasaran B2B/B2C
        ['sales', 'penjualan', 'menjual', 'selling', 'pemasaran', 'marketing', 'canvassing', 'telemarketing', 'promosi penjualan', 'account manager', 'account executive', 'komersial', 'business development', 'b2b sales', 'field sales', 'merchandiser', 'direct selling'],
        // 2. Customer Service & Pelayanan Pelanggan
        ['customer service', 'layanan pelanggan', 'pelayanan pelanggan', 'client relation', 'customer relationship', 'crm', 'umpan balik pelanggan', 'kepuasan pelanggan', 'keluhan pelanggan', 'call center', 'helpdesk', 'contact center', 'client care', 'layanan purna jual'],
        // 3. Komunikasi Bisnis & Public Relations
        ['komunikasi bisnis', 'business communication', 'komunikasi efektif', 'effective communication', 'client communication', 'customer communication', 'komunikasi pelanggan', 'communicate with customers', 'communicate with clients', 'interpersonal skills', 'negosiasi bisnis', 'business negotiation', 'lobi', 'komunikatif', 'diplomasi', 'hubungan masyarakat', 'public relations'],
        // 4. Administrasi & Tata Kelola Dokumen
        ['administrasi', 'administration', 'admin perkantoran', 'filing dokumen', 'arsip', 'data entry', 'tata kelola dokumen', 'pembukuan kantor', 'surat menyurat', 'clerical', 'kesekretariatan', 'rekapitulasi data', 'notulensi rapat'],
        // 5. Aplikasi Komputer & Microsoft Office Perkantoran
        ['microsoft office', 'aplikasi perkantoran', 'excel', 'ms word', 'microsoft word', 'powerpoint', 'spreadsheet', 'pengolahan data perkantoran', 'komputer dasar', 'vlookup', 'pivot table', 'formula excel', 'google workspace', 'google docs', 'google sheets'],
        // 6. Akuntansi & Pembukuan
        ['akuntansi', 'accounting', 'keuangan', 'finance', 'pembukuan akuntansi', 'laporan keuangan', 'penagihan piutang', 'jurnal umum', 'buku besar', 'rekonsiliasi bank'],
        // 7. Perpajakan (Taxation)
        ['pajak', 'taxation', 'brevet pajak', 'efaktur', 'e-faktur', 'pph 21', 'pph 23', 'ppn', 'spt masa', 'spt tahunan', 'perhitungan pajak', 'tax planning', 'kepatuhan pajak'],
        // 8. Keuangan & Financial Planning
        ['manajemen keuangan', 'financial analysis', 'financial planning', 'anggaran biaya', 'budgeting', 'cash flow', 'arus kas', 'analisis laporan keuangan', 'investasi'],
        // 9. Perbankan & Analisis Kredit
        ['perbankan', 'banking', 'teller bank', 'analis kredit', 'credit analyst', 'loan officer', 'funding officer', 'lending officer', 'underwriting', 'manajemen risiko kredit'],
        // 10. Asuransi & Underwriting
        ['asuransi', 'insurance', 'klaim asuransi', 'underwriting asuransi', 'polis asuransi', 'bancassurance'],
        // 11. IT Support & Helpdesk
        ['it support', 'helpdesk it', 'troubleshooting komputer', 'instalasi software', 'hardware pc', 'perawatan komputer', 'teknisi komputer', 'jaringan lan', 'printer sharing'],
        // 12. Software Engineering & Web Development
        ['software development', 'rekayasa perangkat lunak', 'pemrograman', 'developer', 'coding', 'web development', 'frontend developer', 'backend developer', 'fullstack developer', 'laravel', 'react js', 'vue js', 'next js', 'node js', 'api integration', 'restful api', 'rest api', 'restful apis', 'microservices', 'microservices architecture', 'apis'],
        // 13. Mobile App Development
        ['mobile app development', 'aplikasi mobile', 'flutter developer', 'react native', 'android developer', 'ios developer', 'kotlin', 'swift'],
        // 14. Database Management & SQL
        ['database management', 'manajemen database', 'sql query', 'mysql', 'postgresql', 'sql server', 'database administrator', 'dba', 'relational database', 'database administration', 'sql performance tuning', 'sql tuning', 'sql', 'optimasi database', 'administrasi database'],
        // 15. DevOps & Cloud Infrastructure
        ['devops', 'cloud computing', 'aws cloud', 'google cloud', 'azure cloud', 'docker container', 'kubernetes', 'ci cd pipeline', 'linux server', 'server administration'],
        // 16. Cybersecurity & Keamanan Informasi
        ['cybersecurity', 'keamanan informasi', 'network security', 'penetration testing', 'firewall', 'vulnerability assessment', 'iso 27001', 'security compliance'],
        // 17. Technical Pre-Sales & Solution Architecture (IT Enterprise)
        ['solution design', 'solution architecture', 'pre-sales engineer', 'solution presentation', 'technical consulting', 'demand analysis', 'perancangan solusi it', 'enterprise solution'],
        // 18. Data Analysis, SQL & Visualisasi
        ['data analyst', 'analisis data', 'data analytics', 'sql database', 'tableau', 'power bi', 'data visualization', 'dashboard reporting', 'pengolahan dataset', 'excel advance'],
        // 19. Data Science, Machine Learning & AI
        ['data science', 'machine learning', 'artificial intelligence', 'python data science', 'deep learning', 'nlp', 'predictive modeling', 'big data'],
        // 20. Desain Grafis & Creative Media
        ['desain grafis', 'graphic design', 'photoshop', 'canva', 'adobe illustrator', 'kreatif visual', 'creative design', 'layouting', 'tipografi', 'branding visual'],
        // 21. UI/UX Design & Product Design
        ['ui design', 'ux design', 'user interface', 'user experience', 'figma', 'wireframing', 'prototyping', 'design system', 'user research', 'product designer'],
        // 22. Video Editing & Motion Graphics
        ['video editing', 'editor video', 'premiere pro', 'after effects', 'motion graphics', 'color grading', 'produksi video', 'videografi'],
        // 23. Digital Marketing, SEO & SEM
        ['digital marketing', 'pemasaran digital', 'seo', 'search engine optimization', 'sem', 'google ads', 'meta ads', 'facebook ads', 'email marketing', 'conversion rate'],
        // 24. Content Creation & Social Media Management
        ['social media', 'media sosial', 'content creator', 'social media specialist', 'copywriting', 'tiktok marketing', 'manajemen konten digital', 'content planning'],
        // 25. Pergudangan & Inventory Control
        ['pergudangan', 'warehouse', 'manajemen stok', 'inventory control', 'inventaris barang', 'fifo lifo', 'stok opname', 'picking packing', 'bongkar muat', 'surat jalan'],
        // 26. Logistik, Freight Forwarding & Supply Chain
        ['logistik', 'supply chain', 'distribusi barang', 'pengiriman kargo', 'freight forwarding', 'manajemen armada', 'route planning', 'ekspedisi', 'surat muatan'],
        // 27. Perdagangan Internasional, Ekspor Impor & Bea Cukai
        ['perdagangan internasional', 'international trade', 'ekspor impor', 'export import', 'bea cukai', 'customs clearance', 'fta', 'peraturan perdagangan', 'letter of credit', 'bill of lading', 'dokumen ekspor impor', 'shipping instruction', 'peb pib'],
        // 28. Manajemen Risiko & Kepatuhan Bisnis
        ['manajemen risiko', 'risk management', 'penilaian risiko', 'risk assessment', 'mitigasi risiko', 'audit kepatuhan', 'compliance audit', 'tata kelola risiko', 'analisis risiko bisnis'],
        // 29. Manajemen Proyek & Agile/Scrum
        ['manajemen proyek', 'project management', 'project manager', 'agile scrum', 'timeline proyek', 'monitoring progress proyek', 'manajemen stakeholder', 'pmp'],
        // 30. Manajemen Operasional & Standar SOP
        ['manajemen operasional', 'kepemimpinan', 'leadership', 'supervisor', 'koordinator tim', 'manajerial', 'pengawasan operasional', 'standar operasional prosedur', 'sop perusahaan', 'key performance indicator', 'kpi'],
        // 31. HRD, Rekrutmen & Talent Acquisition
        ['human resources', 'hrd', 'personalia', 'rekrutmen', 'recruitment', 'talent acquisition', 'sourcing kandidat', 'interview wawancara', 'onboarding karyawan', 'evaluasi kinerja karyawan'],
        // 32. Payroll, Kompensasi & Hubungan Industrial
        ['payroll gaji', 'penggajian', 'bpjs ketenagakerjaan', 'bpjs kesehatan', 'hubungan industrial', 'ketenagakerjaan', 'kompensasi dan benefit', 'pajak pph 21'],
        // 33. K3 & Keselamatan Kerja (HSE / EHS)
        ['k3', 'keselamatan dan kesehatan kerja', 'hse', 'ehs', 'safety officer', 'smk3', 'alat pelindung diri', 'apd', 'inspeksi k3', 'manajemen keselamatan kerja', 'investigasi insiden', 'tanggap darurat', 'first aid', 'p3k', 'hazard identification'],
        // 34. Quality Control & Quality Assurance (QC/QA)
        ['quality control', 'qc inspector', 'quality assurance', 'qa specialist', 'pengendalian mutu', 'penjaminan mutu', 'iso 9001', 'inspeksi kualitas', 'six sigma', '5s kaizen', 'standar kualitas produksi', 'pengujian produk'],
        // 35. Teknik Mesin & Perawatan Mesin (Maintenance)
        ['teknik mesin', 'mechanical engineering', 'pemeliharaan mesin', 'preventive maintenance', 'perbaikan mesin', 'sistem hidrolik', 'pneumatik', 'troubleshooting mekanikal'],
        // 36. Pengelasan & Fabrikasi Logam (Welder, CNC)
        ['welding', 'pengelasan', 'welder bersertifikat', 'las listrik', 'las argon', 'las tig', 'las mig', 'fabrikasi logam', 'bubut', 'milling', 'cnc operator', 'machining'],
        // 37. Teknik Elektro & Kelistrikan Industri
        ['teknik elektro', 'electrical engineering', 'instalasi listrik', 'panel listrik', 'arus kuat', 'arus lemah', 'wiring listrik', 'kelistrikan industri', 'genset maintenance'],
        // 38. Otomasi Industri, PLC & SCADA
        ['plc programming', 'scada', 'otomasi industri', 'instrumentasi industri', 'sensor industri', 'hmi programming', 'inverter motor'],
        // 39. Teknik Sipil & Manajemen Konstruksi
        ['teknik sipil', 'civil engineering', 'konstruksi bangunan', 'mandor proyek', 'site supervisor', 'quantity surveyor', 'rencana anggaran biaya', 'rab proyek', 'surveyor tanah', 'pengawasan konstruksi'],
        // 40. Arsitektur & Desain Bangunan (AutoCAD, SketchUp)
        ['arsitektur', 'autocad', 'sketchup', 'revit', 'desain interior', 'gambar teknik', 'drafting arsitektur', '3d modeling bangunan'],
        // 41. Otomotif & Mekanik Kendaraan (Mobil/Motor)
        ['teknik otomotif', 'mekanik mobil', 'mekanik motor', 'tune up kendaraan', 'spooring balancing', 'servis kendaraan', 'sistem transmisi', 'sistem pengereman', 'overhaul mesin', 'perawatan armada'],
        // 42. Tata Boga, Chef & Bakery
        ['kuliner', 'chef', 'koki', 'cook', 'tata boga', 'pastry baker', 'memasak', 'pembuatan kue', 'food production', 'haccp keamanan pangan', 'hygiene sanitasi makanan', 'resep makanan'],
        // 43. Food & Beverage Service, Barista & Hospitality
        ['food and beverage service', 'f&b service', 'barista', 'bartender', 'pramusaji', 'waiter waitress', 'pembuatan kopi', 'hospitality f&b'],
        // 44. Perhotelan, Front Office & Housekeeping
        ['hospitality perhotelan', 'housekeeping', 'room attendant', 'front office hotel', 'resepsionis hotel', 'reservasi kamar', 'laundry hotel'],
        // 45. Keperawatan & Pelayanan Medis
        ['keperawatan', 'perawat', 'bidan', 'rekam medis', 'pelayanan pasien', 'laboratorium medis', 'tindakan medis dasar', 'asuhan keperawatan', 'pelayanan kesehatan', 'tenaga kesehatan'],
        // 46. Farmasi & Asisten Apoteker
        ['farmasi', 'asisten apoteker', 'dispensing obat', 'klinik kesehatan', 'pengelolaan obat', 'resep dokter', 'edukasi obat pasien'],
        // 47. Pendidikan, Guru & Instruktur Pelatihan
        ['tenaga pendidik', 'pengajar', 'guru', 'tutor belajar', 'instruktur pelatihan', 'pedagogi', 'kurikulum pembelajaran', 'metode pengajaran', 'penyusunan silabus', 'kegiatan belajar mengajar'],
        // 48. Legal Corporate & Drafting Kontrak
        ['legal corporate', 'staf hukum', 'drafting kontrak', 'perjanjian kerja sama', 'kepatuhan hukum', 'legal compliance', 'perizinan usaha', 'oss rba', 'litigasi', 'konsultasi hukum', 'legal drafting', 'review kontrak', 'commercial contracts', 'contracts and agreements', 'kontrak bisnis', 'perjanjian bisnis', 'review agreement', 'commercial agreements'],
        // 49. Procurement, Purchasing & Manajemen Vendor
        ['pengadaan barang', 'procurement', 'purchasing officer', 'pembelian material', 'manajemen vendor', 'sourcing supplier', 'purchase order', 'negosiasi harga supplier'],
        // 50. Bahasa Asing & Penerjemahan
        ['bahasa inggris', 'english communication', 'toefl', 'toeic', 'ielts', 'bahasa jepang', 'jlpt', 'bahasa mandarin', 'hsk', 'penerjemah', 'translator', 'interpreter'],
        // 51. Keamanan & Pengamanan Fisik (Security Garda Pratama)
        ['security', 'satpam', 'petugas pengamanan', 'patroli keamanan', 'pengamanan aset', 'gardapratama', 'penjagaan pos'],
        // 52. Driver Profesional & Transportasi Logistik
        ['pengemudi', 'sopir profesional', 'driver logistik', 'sim b1', 'sim b2', 'safety driving', 'pengantaran barang', 'navigasi rute'],
        // 53. Kasir, Teller Toko & Point of Sales (POS)
        ['kasir', 'cashier', 'mesin kasir', 'sistem pos', 'point of sales', 'transaksi kasir', 'pembayaran kasir', 'hitung uang kas', 'closing kasir', 'operasional kasir'],
        // 54. Teknologi Informasi & Komputer Umum (General IT & Technologies)
        ['teknologi informasi', 'information technology', 'ti', 'it', 'technologies', 'technology', 'bidang teknologi', 'sistem komputer', 'komputer'],
        // 55. Pemantauan & Analisis Tren Teknologi (Technology Trends, Cloud, AI, IoT)
        ['tren teknologi', 'analisis tren teknologi', 'pemantauan tren teknologi', 'memantau tren teknologi', 'mengikuti perkembangan teknologi', 'technical trend', 'technology trend', 'technical trends', 'technology trends', 'tech trends', 'cloud ai iot', 'adopsi teknologi', 'perkembangan teknologi', 'evaluasi teknologi'],
    ];

    /**
     * Komputasi kesesuaian antara 1 kandidat pencari kerja dan 1 lowongan kerja
     * Menggunakan Multi-Criteria Weighted Matching Engine (Bebas Ketergantungan ID ESCO & KBJI)
     */
    public function matchPair(int $jobSeekerId, string $lowonganId): array
    {
        $lowongan = LowonganKerja::with(['kbji', 'educationLevel', 'province', 'regency', 'skills'])
            ->find($lowonganId);

        if (!$lowongan) {
            throw new \InvalidArgumentException('Lowongan kerja tidak ditemukan.');
        }

        $jobSeeker = JobSeeker::with(['educationLevel', 'regency.province', 'province', 'skills'])
            ->find($jobSeekerId);

        if (!$jobSeeker) {
            throw new \InvalidArgumentException('Profil pencari kerja tidak ditemukan.');
        }

        return $this->computeCompositeMatch($jobSeeker, $lowongan);
    }

    /**
     * Rekomendasi daftar lowongan yang paling cocok untuk seorang kandidat pencari kerja
     */
    public function recommendJobsForSeeker(int $jobSeekerId, array $filters = []): array
    {
        $jobSeeker = JobSeeker::with(['educationLevel', 'regency.province', 'province', 'skills'])
            ->find($jobSeekerId);

        if (!$jobSeeker) {
            throw new \InvalidArgumentException('Profil pencari kerja tidak ditemukan.');
        }

        // Ambil lowongan dari tabel req_pk_loker
        $query = LowonganKerja::query()
            ->with([
                'skills:id,title,title_en',
                'kbji:id,code,title',
                'educationLevel:id,name,sort_order',
                'province:id,name',
                'regency:id,name',
            ]);

        if (!empty($filters['status_lowongan'])) {
            $query->where('status_loker', strtolower($filters['status_lowongan']));
        } else {
            $query->published();
        }

        if (!empty($filters['provinsi_id'])) {
            $query->where('provinsi_id', $filters['provinsi_id']);
        }

        if (!empty($filters['tipe_pekerjaan'])) {
            $query->where('tipe_pekerjaan', 'ILIKE', '%' . $filters['tipe_pekerjaan'] . '%');
        }

        $vacancies = $query->latest('id')->take(50)->get();
        $recommendations = [];

        foreach ($vacancies as $job) {
            $matchResult = $this->computeCompositeMatch($jobSeeker, $job);

            // Jika kualifikasi pendidikan sangat jauh di bawah syarat mutlak, bisa dilewati atau diturunkan
            if (!$matchResult['education_match']['is_matched'] && ($matchResult['education_match']['status'] ?? '') === 'di_bawah_syarat') {
                // Berikan toleransi jika gap hanya 1 tingkat
                if (($matchResult['education_match']['score'] ?? 0) < 40) {
                    continue;
                }
            }

            $recommendations[] = [
                'job_id' => $job->id,
                'slug' => $job->slug,
                'judul_lowongan' => $job->judul_lowongan,
                'nama_perusahaan' => $job->nama_perusahaan,
                'tipe_pekerjaan' => $job->tipe_pekerjaan,
                'sistem_kerja' => $job->sistem_kerja,
                'kbji' => $job->kbji ? [
                    'id' => $job->kbji->id,
                    'code' => $job->kbji->code,
                    'title' => $job->kbji->title,
                ] : null,
                'education_level' => $job->educationLevel?->name,
                'location' => ($job->regency?->name ?? '') . ', ' . ($job->province?->name ?? ''),
                'gaji_tampilkan' => $job->gaji_tampilkan,
                'gaji_minimal' => $job->gaji_minimal,
                'gaji_maksimal' => $job->gaji_maksimal,
                'tanggal_tutup' => $job->tanggal_tutup?->toISOString(),
                'status_lowongan' => $job->status_lowongan,
                'score' => $matchResult['score'],
                'total_required' => $matchResult['total_required'],
                'total_matched' => $matchResult['total_matched'],
                'total_gap' => $matchResult['total_gap'],
                'classification' => $matchResult['classification'],
                'kbji_match' => $matchResult['kbji_match'],
                'education_match' => $matchResult['education_match'],
                'matched_skills' => $matchResult['matched_skills'],
                'gap_skills' => $matchResult['gap_skills'],
                'score_breakdown' => $matchResult['score_breakdown'] ?? null,
            ];
        }

        // Urutkan dari skor tertinggi ke terendah
        usort($recommendations, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return $recommendations;
    }

    /**
     * Rekomendasi daftar kandidat pencari kerja yang paling cocok untuk sebuah lowongan
     */
    public function recommendCandidatesForJob(string $lowonganId, array $filters = []): array
    {
        $lowongan = LowonganKerja::with(['kbji', 'educationLevel', 'province', 'regency', 'skills'])
            ->find($lowonganId);

        if (!$lowongan) {
            throw new \InvalidArgumentException('Lowongan kerja tidak ditemukan.');
        }

        // Query kandidat dengan pencarian relevan & efisien (hindari memuat seluruh 900k baris sekaligus)
        $query = JobSeeker::query()
            ->with([
                'educationLevel:id,name,sort_order',
                'regency.province',
                'province',
                'skills',
            ]);

        if (!empty($filters['regency_id'])) {
            $query->where('regency_id', $filters['regency_id']);
        }

        // Filter kandidat yang memiliki profil bermakna (ada nama, keahlian, pengalaman, atau pendidikan)
        $query->where(function ($q) {
            $q->whereNotNull('keahlian')
              ->orWhereNotNull('experience')
              ->orWhereNotNull('sertifikasi');
        });

        // Ambil sampel relevan hingga 150 kandidat untuk dinilai
        $seekers = $query->latest('id')->take(150)->get();
        $recommendations = [];

        foreach ($seekers as $candidate) {
            $matchResult = $this->computeCompositeMatch($candidate, $lowongan);

            $recommendations[] = [
                'candidate_id' => $candidate->id,
                'full_name' => $candidate->full_name,
                'nik' => $candidate->nik,
                'phone' => $candidate->phone,
                'gender' => $candidate->jenis_kelamin,
                'education_level' => $candidate->educationLevel?->name ?? $candidate->pendidikan,
                'study_field_group' => $candidate->jurusan,
                'experience_range' => $candidate->experience_range ?? null,
                'desired_occupation' => $candidate->desired_occupation,
                'kbji' => $candidate->kbji ? [
                    'id' => $candidate->kbji->id,
                    'code' => $candidate->kbji->code,
                    'title' => $candidate->kbji->title,
                ] : null,
                'location' => ($candidate->regency?->name ?? $candidate->kab_kota ?? '') . ', ' . ($candidate->province?->name ?? $candidate->provinsi ?? ''),
                'score' => $matchResult['score'],
                'total_required' => $matchResult['total_required'],
                'total_matched' => $matchResult['total_matched'],
                'total_gap' => $matchResult['total_gap'],
                'classification' => $matchResult['classification'],
                'kbji_match' => $matchResult['kbji_match'],
                'education_match' => $matchResult['education_match'],
                'matched_skills' => $matchResult['matched_skills'],
                'gap_skills' => $matchResult['gap_skills'],
                'score_breakdown' => $matchResult['score_breakdown'] ?? null,
            ];
        }

        // Urutkan dari skor tertinggi
        usort($recommendations, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return array_slice($recommendations, 0, 50);
    }

    /**
     * Rekomendasi Terpadu Pasangan (Unified Pairwise Recommendations)
     */
    public function getUnifiedRecommendations(array $filters = []): array
    {
        // 1. Query Pencari Kerja (jika tidak ada filter pencaker spesifik, batasi ke sampel aktif terbaru)
        $seekerQuery = JobSeeker::query()
            ->with([
                'educationLevel:id,name,sort_order',
                'regency.province',
                'province',
                'skills',
            ]);

        if (!empty($filters['job_seeker_id'])) {
            $seekerQuery->where('id', $filters['job_seeker_id']);
        } else {
            // Ambil batch representatif yang memiliki keahlian terdata
            $seekerQuery->where(function ($q) {
                $q->whereNotNull('keahlian')
                  ->orWhereNotNull('experience');
            })->latest('id')->take(30);
        }

        $seekers = $seekerQuery->get();

        // 2. Query Lowongan Kerja
        $jobQuery = LowonganKerja::published()
            ->with([
                'skills:id,title,title_en',
                'kbji:id,code,title',
                'educationLevel:id,name,sort_order',
                'province:id,name',
                'regency:id,name',
            ]);

        if (!empty($filters['lowongan_id'])) {
            $jobQuery->where('id', $filters['lowongan_id']);
        }

        $vacancies = $jobQuery->get();

        $search = !empty($filters['search']) ? mb_strtolower(trim($filters['search'])) : null;
        $categoryFilter = !empty($filters['category']) ? $filters['category'] : null;
        $minScore = isset($filters['min_score']) && is_numeric($filters['min_score']) ? (int) $filters['min_score'] : null;

        $results = [];

        foreach ($seekers as $candidate) {
            foreach ($vacancies as $job) {
                // Filter pencarian teks jika diberikan
                if ($search) {
                    $matchedText = str_contains(mb_strtolower($candidate->full_name), $search)
                        || str_contains(mb_strtolower($candidate->nik), $search)
                        || str_contains(mb_strtolower($candidate->desired_occupation ?? ''), $search)
                        || str_contains(mb_strtolower($job->judul_lowongan), $search)
                        || str_contains(mb_strtolower($job->nama_perusahaan), $search);

                    if (!$matchedText) {
                        continue;
                    }
                }

                $matchResult = $this->computeCompositeMatch($candidate, $job);
                $score = $matchResult['score'];
                $classification = $matchResult['classification'];

                // Filter kategori jika diberikan
                if ($categoryFilter) {
                    if ($categoryFilter === 'ready' || $categoryFilter === 'high') {
                        if ($score < 65) {
                            continue;
                        }
                    } elseif ($classification['category'] !== $categoryFilter) {
                        continue;
                    }
                }

                // Filter minimal skor jika diberikan
                if ($minScore !== null && $score < $minScore) {
                    continue;
                }

                $results[] = [
                    'id' => $candidate->id . '_' . $job->id,
                    'score' => $score,
                    'total_required' => $matchResult['total_required'],
                    'total_matched' => $matchResult['total_matched'],
                    'total_gap' => $matchResult['total_gap'],
                    'classification' => $classification,
                    'kbji_match' => $matchResult['kbji_match'],
                    'education_match' => $matchResult['education_match'],
                    'score_breakdown' => $matchResult['score_breakdown'] ?? null,
                    'candidate' => [
                        'id' => $candidate->id,
                        'nik' => $candidate->nik,
                        'full_name' => $candidate->full_name,
                        'phone' => $candidate->phone,
                        'education_level' => $candidate->educationLevel?->name ?? $candidate->pendidikan,
                        'desired_occupation' => $candidate->desired_occupation,
                        'kbji' => $candidate->kbji ? [
                            'id' => $candidate->kbji->id,
                            'code' => $candidate->kbji->code,
                            'title' => $candidate->kbji->title,
                        ] : null,
                        'location' => ($candidate->regency?->name ?? $candidate->kab_kota ?? '') . ', ' . ($candidate->province?->name ?? $candidate->provinsi ?? ''),
                    ],
                    'job' => [
                        'id' => $job->id,
                        'slug' => $job->slug,
                        'judul_lowongan' => $job->judul_lowongan,
                        'nama_perusahaan' => $job->nama_perusahaan,
                        'tipe_pekerjaan' => $job->tipe_pekerjaan,
                        'sistem_kerja' => $job->sistem_kerja,
                        'kbji' => $job->kbji ? [
                            'id' => $job->kbji->id,
                            'code' => $job->kbji->code,
                            'title' => $job->kbji->title,
                        ] : null,
                        'education_level' => $job->educationLevel?->name,
                        'location' => ($job->regency?->name ?? '') . ', ' . ($job->province?->name ?? ''),
                        'gaji_tampilkan' => $job->gaji_tampilkan,
                        'gaji_minimal' => $job->gaji_minimal,
                        'gaji_maksimal' => $job->gaji_maksimal,
                    ],
                    'matched_skills' => $matchResult['matched_skills'],
                    'gap_skills' => $matchResult['gap_skills'],
                ];
            }
        }

        // Urutkan dari skor tertinggi
        usort($results, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return $results;
    }

    /**
     * Algoritma Komputasi Multi-Criteria Weighted Match
     * Menggabungkan 4 Dimensi:
     * 1. Kesesuaian Keahlian & Kualifikasi / Kompetensi (40%)
     * 2. Kesesuaian Pendidikan & Bidang Studi (30%)
     * 3. Kesesuaian Pengalaman Kerja (20%)
     * 4. Kesesuaian Lokasi & Sistem Kerja (10%)
     */
    public function computeCompositeMatch(JobSeeker $candidate, object $job): array
    {
        // 1. Evaluasi Dimensi Keahlian (Skills & Competencies) - Bobot 40%
        $skillMatch = $this->evaluateCompetencyMatch($candidate, $job);
        $skillScore = $skillMatch['score'];

        // 2. Evaluasi Dimensi Pendidikan (Education) - Bobot 30%
        $eduMatch = $this->evaluateEducationAndFieldMatch($candidate, $job);
        $eduScore = $eduMatch['score'];

        // 3. Evaluasi Dimensi Pengalaman (Experience) - Bobot 20%
        $expMatch = $this->evaluateExperienceMatch($candidate, $job);
        $expScore = $expMatch['score'];

        // 4. Evaluasi Dimensi Lokasi (Location) - Bobot 10%
        $locMatch = $this->evaluateLocationMatch($candidate, $job);
        $locScore = $locMatch['score'];

        // Hitung Komposit Skor Berbobot (Total 100%)
        $totalWeightedScore = (int) round(
            (0.40 * $skillScore) +
            (0.30 * $eduScore) +
            (0.20 * $expScore) +
            (0.10 * $locScore)
        );

        $totalWeightedScore = max(0, min(100, $totalWeightedScore));

        // Klasifikasi Skor
        $classification = $this->classifyScore($totalWeightedScore);

        // Jika jenjang pendidikan kandidat di bawah syarat minimal, berikan penanda diskualifikasi
        if (!$eduMatch['education_level_match']['is_matched']) {
            $classification = [
                'category' => 'disqualified',
                'label' => 'Pendidikan Tidak Memenuhi',
                'badge_color' => 'rose',
                'action_label' => 'Tidak Memenuhi Syarat',
                'action_key' => 'not_eligible_education',
                'description' => 'Jenjang pendidikan kandidat (' . ($candidate->educationLevel?->name ?? $candidate->pendidikan ?? 'Belum Terdata') . ') di bawah syarat minimal lowongan (' . ($job->educationLevel?->name ?? 'Min. Tertentu') . ').',
            ];
        }

        $matchedSkills = $skillMatch['matched_skills'];
        $gapSkills = $skillMatch['gap_skills'];
        $totalRequired = count($matchedSkills) + count($gapSkills);

        $kbjiMatch = $this->evaluateKbjiMatch($candidate->kbji, $job->kbji);

        return [
            'candidate' => [
                'id' => $candidate->id,
                'full_name' => $candidate->full_name,
                'nik' => $candidate->nik,
                'phone' => $candidate->phone,
                'education_level' => $candidate->educationLevel?->name ?? $candidate->pendidikan,
                'desired_occupation' => $candidate->desired_occupation,
                'kbji' => $candidate->kbji ? [
                    'code' => $candidate->kbji->code,
                    'title' => $candidate->kbji->title,
                ] : null,
                'location' => ($candidate->regency?->name ?? $candidate->kab_kota ?? '') . ', ' . ($candidate->province?->name ?? $candidate->provinsi ?? ''),
            ],
            'job' => [
                'id' => $job->id,
                'judul_lowongan' => $job->judul_lowongan,
                'nama_perusahaan' => $job->nama_perusahaan,
                'tipe_pekerjaan' => $job->tipe_pekerjaan,
                'sistem_kerja' => $job->sistem_kerja,
                'kbji' => $job->kbji ? [
                    'code' => $job->kbji->code,
                    'title' => $job->kbji->title,
                ] : null,
                'education_level' => $job->educationLevel?->name,
                'location' => ($job->regency?->name ?? '') . ', ' . ($job->province?->name ?? ''),
                'status_lowongan' => $job->status_lowongan,
            ],
            'score' => $totalWeightedScore,
            'total_required' => $totalRequired,
            'total_matched' => count($matchedSkills),
            'total_gap' => count($gapSkills),
            'classification' => $classification,
            'kbji_match' => $kbjiMatch,
            'education_match' => $eduMatch['education_level_match'],
            'matched_skills' => $matchedSkills,
            'gap_skills' => $gapSkills,
            'score_breakdown' => [
                'skill_score' => $skillScore,
                'education_score' => $eduScore,
                'experience_score' => $expScore,
                'location_score' => $locScore,
            ],
        ];
    }

    /**
     * Dimensi 1: Evaluasi Kesesuaian Jabatan & Peran (Bobot 35%)
     * 100% JUDUL & DESKRIPSI FIRST:
     * Menilai kesesuaian MURNI dari Judul Lowongan & Deskripsi Pekerjaan
     * terhadap Target Okupasi, Riwayat Pekerjaan (Experience), dan Keahlian Kandidat.
     * KBJI TIDAK LAGI memengaruhi skor, hanya sebagai metadata informasional untuk UI.
     */
    protected function evaluateRoleMatch(JobSeeker $candidate, object $job): array
    {
        $jobTitle = $job->judul_lowongan ?? $job->judul_pekerjaan ?? '';
        $jobDesc = $job->deskripsi_pekerjaan ?? '';
        $jobContext = mb_strtolower(trim($jobTitle . ' ' . $jobDesc));

        $candidateRole = $candidate->desired_occupation ?? '';
        $experienceText = $candidate->experience ?? '';

        // Kumpulkan kandidat peran dari desired_occupation dan riwayat kerja (experience)
        $candidateRoles = [];
        if (!empty($candidateRole)) {
            $candidateRoles[] = $candidateRole;
        }
        if (preg_match_all('/\[([^;\]]+)/', $experienceText, $matches)) {
            foreach ($matches[1] as $r) {
                $candidateRoles[] = trim($r);
            }
        }

        // Jika belum ada peran spesifik, gunakan jurusan atau keahlian utama sebagai proksi
        if (empty($candidateRoles)) {
            if (!empty($candidate->jurusan)) {
                $candidateRoles[] = $candidate->jurusan;
            }
            if (!empty($candidate->keahlian)) {
                $skills = $this->extractSkillsFromText($candidate->keahlian);
                if (!empty($skills)) {
                    $candidateRoles[] = $skills[0];
                }
            }
        }

        $bestScore = 0; // Baseline jika tidak ada kesamaan (0%)
        $jobTitleLower = mb_strtolower($jobTitle);
        $jobTitleTokens = $this->tokenizeText($jobTitle);
        $jobDescTokens = $this->tokenizeText($jobDesc);

        foreach ($candidateRoles as $role) {
            $roleLower = mb_strtolower($role);
            $roleTokens = $this->tokenizeText($role);

            if (empty($roleTokens)) continue;

            // Kasus 1A: Judul persis sama (Exact Match 100%)
            if ($jobTitleLower === $roleLower) {
                $bestScore = max($bestScore, 100);
                continue;
            }

            // Kasus 1B: Substring lengkap (misal: "Frontend Developer" di "Senior Frontend Developer")
            if (str_contains($jobTitleLower, $roleLower) || str_contains($roleLower, $jobTitleLower)) {
                $bestScore = max($bestScore, 95);
                continue;
            }

            // Kasus 2: Peran kandidat disebut langsung di dalam deskripsi pekerjaan
            // (contoh: kandidat "Kasir", lowongan "Crew Outlet" tapi deskripsi ada "melayani transaksi kasir")
            if (str_contains($jobContext, $roleLower)) {
                $bestScore = max($bestScore, 85);
                continue;
            }

            // Kasus 3: Overlap kata kunci penting pada Judul Pekerjaan
            $commonTitleTokens = array_intersect($roleTokens, $jobTitleTokens);
            if (!empty($commonTitleTokens)) {
                $ratio = count($commonTitleTokens) / max(count($roleTokens), count($jobTitleTokens));
                $tokenScore = (int) round(60 + ($ratio * 35)); // Rentang 60 - 95%
                $bestScore = max($bestScore, $tokenScore);
                continue;
            }

            // Kasus 4: Overlap kata kunci peran dengan Deskripsi Pekerjaan
            $commonDescTokens = array_intersect($roleTokens, $jobDescTokens);
            if (!empty($commonDescTokens)) {
                $descRatio = count($commonDescTokens) / count($roleTokens);
                $tokenScore = (int) round(50 + ($descRatio * 30)); // Rentang 50 - 80%
                $bestScore = max($bestScore, $tokenScore);
                continue;
            }

            // Kasus 5: Kemiripan teks fuzzy Levenshtein / Dice
            $sim = $this->calculateTextSimilarity($jobTitle, $role);
            if ($sim >= 0.4) {
                $bestScore = max($bestScore, (int) round($sim * 100));
            }
        }

        // KBJI hanya sebagai metadata pendamping (tidak mendikte skor)
        $kbjiMatch = $this->evaluateKbjiMatch($candidate->kbji, $job->kbji);

        return [
            'score' => min(100, max(0, $bestScore)),
            'kbji_match' => $kbjiMatch,
            'title_similarity' => $bestScore / 100,
        ];
    }

    /**
     * Dimensi 2: Evaluasi Keahlian & Kualifikasi Profil (Bobot 30%)
     * Mengekstrak poin-poin kompetensi profil kandidat (keahlian, sertifikasi, experience)
     * dan mencocokkannya dengan kebutuhan lowongan secara tekstual & semantik.
     */
    /**
     * Dimensi 1 (Utama): Evaluasi Keahlian & Kualifikasi Profil (Bobot 40%)
     * 100% BEBAS TAKSONOMI ESCO / KBJI:
     * Menilai kesesuaian murni dari teks riil kompetensi profil kandidat (keahlian, sertifikasi, experience)
     * terhadap deskripsi, persyaratan, dan tag skill lowongan melalui semantic & synonym clustering.
     */
    protected function evaluateCompetencyMatch(JobSeeker $candidate, object $job): array
    {
        $candKeahlian = trim($candidate->keahlian ?? '');
        $candSertifikasi = trim($candidate->sertifikasi ?? '');
        $candExperience = trim($candidate->experience ?? '');

        // Kumpulkan item skill mandiri kandidat dari keahlian, sertifikasi, dan relasi jika ada (nama teksnya)
        $candidateSkillItems = $this->extractSkillsFromText($candKeahlian);
        if (!empty($candSertifikasi)) {
            $candidateSkillItems = array_merge($candidateSkillItems, $this->extractSkillsFromText($candSertifikasi));
        }
        if (!empty($candidate->skills) && $candidate->skills->isNotEmpty()) {
            foreach ($candidate->skills as $csk) {
                if (!empty($csk->title)) $candidateSkillItems[] = $csk->title;
                if (!empty($csk->title_en)) $candidateSkillItems[] = $csk->title_en;
            }
        }
        $candidateSkillItems = array_values(array_unique(array_filter($candidateSkillItems)));

        $rawProfileText = mb_strtolower(trim($candKeahlian . ' ' . $candSertifikasi . ' ' . $candExperience . ' ' . implode(' ', $candidateSkillItems)));
        $profileTokens = $this->tokenizeText($rawProfileText);

        $jobTitle = $job->judul_lowongan ?? $job->judul_pekerjaan ?? '';
        $jobDesc = $job->deskripsi_pekerjaan ?? '';
        $jobPersyaratan = $job->persyaratan_tambahan ?? '';
        $jobReqText = mb_strtolower(trim($jobTitle . ' ' . $jobDesc . ' ' . $jobPersyaratan));
        $jobReqTokens = $this->tokenizeText($jobReqText);

        $matchedSkills = [];
        $gapSkills = [];
        $matchedSkillTitles = [];
        $dummyId = 1000;

        // 1. Kumpulkan Target Kebutuhan Skill Lowongan
        $targetRequirements = [];

        // A. Prioritaskan Ekstraksi Poin Kualifikasi Riil dari Deskripsi & Persyaratan Lowongan
        $extractedFromText = $this->extractRequirementsFromJobText($jobDesc . "\n" . $jobPersyaratan, $jobTitle);

        if (!empty($extractedFromText) && count($extractedFromText) >= 2) {
            // Jika deskripsi pekerjaan memiliki poin kualifikasi riil, gunakan langsung
            foreach ($extractedFromText as $ext) {
                $targetRequirements[] = [
                    'id' => ++$dummyId,
                    'title' => $ext['title'],
                    'raw_text' => $ext['raw_text'] ?? $ext['title'],
                    'title_en' => null,
                    'tipe_keahlian' => $ext['tipe_keahlian'] ?? 'wajib',
                    'level_kemahiran' => $ext['level_kemahiran'] ?? 'menengah',
                ];
            }
        } elseif (!empty($job->skills) && $job->skills->isNotEmpty()) {
            // B. Fallback: gunakan tag lowongan yang bersih (saring istilah taksonomi asing/aneh)
            $cleanJobSkills = $job->skills->filter(function($js) {
                $t = mb_strtolower($js->title ?? $js->name ?? '');
                return !str_contains($t, 'kebersihan industri') && 
                       !str_contains($t, 'hewan') && 
                       !str_contains($t, 'kebun anggur') && 
                       !str_contains($t, 'kehutanan') &&
                       mb_strlen($t) >= 3;
            });
            if ($cleanJobSkills->isNotEmpty()) {
                foreach ($cleanJobSkills as $js) {
                    $targetRequirements[] = [
                        'id' => ++$dummyId,
                        'title' => $js->title ?? $js->name ?? '',
                        'raw_text' => $js->title ?? $js->name ?? '',
                        'title_en' => $js->title_en ?? null,
                        'tipe_keahlian' => $js->pivot->tipe_keahlian ?? 'wajib',
                        'level_kemahiran' => $js->pivot->level_kemahiran ?? 'menengah',
                    ];
                }
            }
        }

        // C. Universal Fallback: Jika masih kosong atau kurang dari 2 (karena deskripsi berupa narasi singkat/hanya judul),
        // sintesiskan kompetensi standar profesional berdasarkan judul formasi lowongan
        if (count($targetRequirements) < 2) {
            $synthesized = $this->synthesizeCompetenciesFromJobTitle($jobTitle, $jobDesc . ' ' . $jobPersyaratan);
            foreach ($synthesized as $syn) {
                $targetRequirements[] = [
                    'id' => ++$dummyId,
                    'title' => $syn['title'],
                    'raw_text' => $syn['title'],
                    'title_en' => null,
                    'tipe_keahlian' => $syn['tipe_keahlian'] ?? 'wajib',
                    'level_kemahiran' => 'menengah',
                ];
            }
        }

        // 2. Evaluasi Setiap Target Requirement (Bebas ID Taksonomi)
        foreach ($targetRequirements as $req) {
            $reqTitle = $req['title'];
            $reqRawText = $req['raw_text'] ?? $reqTitle;
            if (empty(trim($reqTitle))) continue;

            // Evaluasi kecocokan menggunakan gabungan judul ringkas dan teks kualifikasi lengkap
            $semanticTargetText = $reqTitle . ($reqRawText !== $reqTitle ? ' ' . $reqRawText : '');

            $match = $this->matchSkillConcept(
                $semanticTargetText,
                $req['title_en'] ?? null,
                $candidateSkillItems,
                $rawProfileText,
                $profileTokens
            );

            if ($match['matched']) {
                $matchedSkills[] = [
                    'id' => $req['id'],
                    'title' => $reqTitle,
                    'raw_description' => $reqRawText,
                    'title_en' => $req['title_en'] ?? null,
                    'tipe_keahlian' => $req['tipe_keahlian'],
                    'level_kemahiran' => $req['level_kemahiran'],
                    'match_type' => $match['type'],
                    'matched_with' => $match['matched_with'],
                    'status_verifikasi' => $match['status_verifikasi'],
                ];
                $matchedSkillTitles[] = mb_strtolower($reqTitle);
            } else {
                $gapSkills[] = [
                    'id' => $req['id'],
                    'title' => $reqTitle,
                    'raw_description' => $reqRawText,
                    'title_en' => $req['title_en'] ?? null,
                    'tipe_keahlian' => $req['tipe_keahlian'],
                    'level_kemahiran' => $req['level_kemahiran'],
                    'status' => 'missing_gap',
                ];
            }
        }

        // 3. Tambahan: Cek Keahlian Riil Kandidat yang Secara Jelas Disebut di Deskripsi Pekerjaan
        // Hanya tambahkan jika keahlian tersebut belum pernah terpakai untuk memenuhi target requirement
        $alreadyMatchedValues = array_map('mb_strtolower', array_filter(array_column($matchedSkills, 'matched_with')));

        foreach ($candidateSkillItems as $cSkill) {
            $cSkillLower = mb_strtolower(trim($cSkill));
            if (empty($cSkillLower) || mb_strlen($cSkillLower) < 3) continue;

            // Lewati jika sudah cocok
            if (in_array($cSkillLower, $matchedSkillTitles) || in_array($cSkillLower, $alreadyMatchedValues)) continue;

            // Cek apakah keahlian kandidat disebut di deskripsi lowongan secara substantif
            $hasMatch = false;
            $matchedWord = $cSkill;

            // 1. Exact phrase dengan word boundary
            if (preg_match('/\b' . preg_quote($cSkillLower, '/') . '\b/iu', $jobReqText)) {
                $hasMatch = true;
            } else {
                // 2. Token overlap: Jangan izinkan 1 token pendek acak memicu kecocokan skill multi-kata
                $cTokens = array_values(array_filter($this->tokenizeText($cSkillLower), function($t) {
                    return mb_strlen($t) >= 4 && !in_array($t, $this->stopWords);
                }));

                if (count($cTokens) === 1) {
                    // Jika skill 1 kata substantif, wajib ada di token lowongan
                    if (in_array($cTokens[0], $jobReqTokens)) {
                        $hasMatch = true;
                        $matchedWord = $cTokens[0];
                    }
                } elseif (count($cTokens) >= 2) {
                    // Jika skill multi-kata, minimal 2 kata substantif DAN >= 60% token harus cocok
                    $common = array_intersect($cTokens, $jobReqTokens);
                    $threshold = max(2, (int) ceil(count($cTokens) * 0.6));
                    if (count($common) >= $threshold) {
                        $hasMatch = true;
                        $matchedWord = implode(', ', $common);
                    }
                }
            }

            if ($hasMatch) {
                $matchedSkills[] = [
                    'id' => ++$dummyId,
                    'title' => $cSkill,
                    'title_en' => null,
                    'tipe_keahlian' => 'tambahan',
                    'level_kemahiran' => 'menengah',
                    'match_type' => 'Keahlian Terpenuhi di CV',
                    'matched_with' => $matchedWord,
                    'status_verifikasi' => 'Sesuai Deskripsi Pekerjaan',
                ];
                $matchedSkillTitles[] = $cSkillLower;
            }
        }

        // 4. Hitung Skor Keahlian Proporsional Murni
        $totalItems = count($matchedSkills) + count($gapSkills);

        if ($totalItems > 0) {
            $totalWeight = 0;
            $matchedWeight = 0;
            foreach ($matchedSkills as $m) {
                $w = ($m['tipe_keahlian'] ?? 'wajib') === 'tambahan' ? 0.7 : 1.0;
                $matchedWeight += $w;
                $totalWeight += $w;
            }
            foreach ($gapSkills as $g) {
                $w = ($g['tipe_keahlian'] ?? 'wajib') === 'tambahan' ? 0.7 : 1.0;
                $totalWeight += $w;
            }

            $skillScore = $totalWeight > 0 
                ? (int) round(($matchedWeight / $totalWeight) * 100) 
                : 0;
        } else {
            // Fallback token coverage jika tidak ada list item
            $common = count(array_intersect($profileTokens, $jobReqTokens));
            $skillScore = min(100, (int) round(($common / max(1, min(10, count($jobReqTokens)))) * 100));
        }

        return [
            'score' => min(100, max(0, $skillScore)),
            'matched_skills' => $matchedSkills,
            'gap_skills' => $gapSkills,
        ];
    }

    /**
     * Pencocokan konsep skill murni berbasis teks & semantik (Bebas ID Taksonomi)
     */
    protected function matchSkillConcept(
        string $targetSkill,
        ?string $targetSkillEn,
        array $candidateSkillItems,
        string $rawProfileText,
        array $profileTokens
    ): array {
        $targetLower = mb_strtolower(trim($targetSkill));
        $targetEnLower = !empty($targetSkillEn) ? mb_strtolower(trim($targetSkillEn)) : '';

        // 1. Cek Exact atau Substring Berbatas Kata pada Teks Profil
        // Hanya izinkan jika panjang target >= 5 karakter dan bounded \b...\b (mencegah false match seperti "it", "cv", "pos")
        if ($targetLower !== '' && mb_strlen($targetLower) >= 5 && preg_match('/\b' . preg_quote($targetLower, '/') . '\b/iu', $rawProfileText)) {
            return [
                'matched' => true,
                'type' => 'Padanan Teks Profil',
                'matched_with' => $targetSkill,
                'status_verifikasi' => 'Terpenuhi di CV/Profil',
            ];
        }

        if ($targetEnLower !== '' && mb_strlen($targetEnLower) >= 5 && preg_match('/\b' . preg_quote($targetEnLower, '/') . '\b/iu', $rawProfileText)) {
            return [
                'matched' => true,
                'type' => 'Padanan Teks Profil (EN)',
                'matched_with' => $targetSkillEn,
                'status_verifikasi' => 'Terpenuhi di CV/Profil',
            ];
        }

        // Cek pada daftar item keahlian kandidat
        foreach ($candidateSkillItems as $cSkill) {
            $cSkillLower = mb_strtolower(trim($cSkill));
            if ($cSkillLower === '' || mb_strlen($cSkillLower) < 3) continue;

            $isExactMatch = ($targetLower === $cSkillLower);
            $minLen = min(mb_strlen($targetLower), mb_strlen($cSkillLower));
            $maxLen = max(mb_strlen($targetLower), mb_strlen($cSkillLower));
            $lengthRatio = $minLen / max(1, $maxLen);

            // Substring hanya sah jika rasio panjang >= 70% dan berbatas kata (mencegah kata umum pendek mencocokkan requirement kompleks)
            $isBoundSubstring = ($lengthRatio >= 0.70) && (
                preg_match('/\b' . preg_quote($cSkillLower, '/') . '\b/iu', $targetLower) ||
                preg_match('/\b' . preg_quote($targetLower, '/') . '\b/iu', $cSkillLower)
            );

            if ($isExactMatch || $isBoundSubstring) {
                return [
                    'matched' => true,
                    'type' => 'Keahlian Terpenuhi',
                    'matched_with' => $cSkill,
                    'status_verifikasi' => 'Sesuai Profil Kandidat',
                ];
            }

            if ($targetEnLower !== '') {
                $isExactEn = ($targetEnLower === $cSkillLower);
                $minLenEn = min(mb_strlen($targetEnLower), mb_strlen($cSkillLower));
                $maxLenEn = max(mb_strlen($targetEnLower), mb_strlen($cSkillLower));
                $lengthRatioEn = $minLenEn / max(1, $maxLenEn);

                $isBoundSubstringEn = ($lengthRatioEn >= 0.70) && (
                    preg_match('/\b' . preg_quote($cSkillLower, '/') . '\b/iu', $targetEnLower) ||
                    preg_match('/\b' . preg_quote($targetEnLower, '/') . '\b/iu', $cSkillLower)
                );

                if ($isExactEn || $isBoundSubstringEn) {
                    return [
                        'matched' => true,
                        'type' => 'Keahlian Terpenuhi (EN)',
                        'matched_with' => $cSkill,
                        'status_verifikasi' => 'Sesuai Profil Kandidat',
                    ];
                }
            }
        }

        // 2. Cek Padanan Melalui Klaster Sinonim & Konsep Industri
        $targetTokens = $this->tokenizeText($targetLower . ' ' . $targetEnLower);

        // 2a. Evaluasi Konsep Dwibahasa Sistemik (Bilingual Canonical Concepts)
        $canonTarget = $this->canonicalizeBilingualConcept($targetLower . ' ' . $targetEnLower);
        if (!empty($canonTarget)) {
            $targetCanonCount = count($canonTarget);

            foreach ($candidateSkillItems as $cSkill) {
                $canonCand = $this->canonicalizeBilingualConcept($cSkill);
                if (!empty($canonCand)) {
                    $commonCanon = array_values(array_intersect($canonTarget, $canonCand));
                    $canonMatchCount = count($commonCanon);

                    // Kasus 1: Target tunggal 1 konsep utama (misal: "Technologies" -> ["teknologi"] vs "Teknologi Informasi" -> ["teknologi", "informasi"])
                    if ($targetCanonCount === 1 && $canonMatchCount === 1) {
                        return [
                            'matched' => true,
                            'type' => 'Padanan Konsep & Sinonim',
                            'matched_with' => $cSkill,
                            'status_verifikasi' => 'Sesuai Konsep Dwibahasa',
                        ];
                    }

                    // Kasus 2: Multi-konsep (misal: "Business Development" -> ["bisnis", "kembang"] vs "Pengembangan Bisnis" -> ["kembang", "bisnis"])
                    // Wajib minimal 2 konsep substantif cocok DAN rasio >= 50%
                    if ($canonMatchCount >= 2) {
                        $ratio = $canonMatchCount / max(1, $targetCanonCount);
                        if ($ratio >= 0.50 || ($canonMatchCount / count($canonCand)) >= 0.50) {
                            return [
                                'matched' => true,
                                'type' => 'Padanan Konsep & Sinonim',
                                'matched_with' => $cSkill,
                                'status_verifikasi' => 'Sesuai Konsep Dwibahasa',
                            ];
                        }
                    }
                }
            }

            // Kasus 3: Cakupan Gabungan Antar-Keahlian Kandidat (Collective Skill Coverage)
            // Jika requirement mencakup gabungan 2 konsep (misal: "Warehouse & Inventory", "Vendor & Purchasing")
            // dan kandidat memiliki masing-masing keahlian secara terpisah di profilnya
            if ($targetCanonCount >= 2) {
                $allCandidateCanon = [];
                $matchingSkillNames = [];
                foreach ($candidateSkillItems as $cSkill) {
                    $cCanon = $this->canonicalizeBilingualConcept($cSkill);
                    if (!empty($cCanon)) {
                        $intersect = array_intersect($canonTarget, $cCanon);
                        if (!empty($intersect)) {
                            $allCandidateCanon = array_merge($allCandidateCanon, $intersect);
                            $matchingSkillNames[] = $cSkill;
                        }
                    }
                }
                $allCandidateCanon = array_values(array_unique($allCandidateCanon));
                $collectiveCommon = array_values(array_intersect($canonTarget, $allCandidateCanon));

                if (count($collectiveCommon) >= 2 && (count($collectiveCommon) / $targetCanonCount) >= 0.50) {
                    return [
                        'matched' => true,
                        'type' => 'Padanan Konsep & Sinonim',
                        'matched_with' => implode(' + ', array_unique($matchingSkillNames)),
                        'status_verifikasi' => 'Sesuai Kombinasi Keahlian Profil',
                    ];
                }
            }
        }

        foreach ($this->skillSynonymClusters as $cluster) {
            $matchesTargetCluster = false;
            foreach ($cluster as $syn) {
                $synLower = mb_strtolower($syn);
                if (mb_strlen($synLower) <= 3) {
                    if (in_array($synLower, $targetTokens)) {
                        $matchesTargetCluster = true;
                        break;
                    }
                } else {
                    // Gunakan batas kata (\b) agar 'terinformasi' tidak mencocokkan 'informasi'
                    if (preg_match('/\b' . preg_quote($synLower, '/') . '\b/iu', $targetLower) || in_array($synLower, $targetTokens)) {
                        $matchesTargetCluster = true;
                        break;
                    }
                    // Dukung kecocokan frasa konsep jika diselingi kata sifat (misal: "communicate with major customers" mencakup "communicate with customers")
                    $synSubstantives = array_values(array_filter($this->tokenizeText($synLower), fn($t) => mb_strlen($t) >= 4 && !in_array($t, $this->stopWords)));
                    if (count($synSubstantives) >= 2) {
                        $commonSyn = array_intersect($synSubstantives, $targetTokens);
                        if (count($commonSyn) === count($synSubstantives)) {
                            $matchesTargetCluster = true;
                            break;
                        }
                    }
                }
            }

            if ($matchesTargetCluster) {
                // 1. Prioritaskan mencocokkan dengan keahlian eksplisit kandidat ($candidateSkillItems)
                foreach ($candidateSkillItems as $cSkill) {
                    $cLower = mb_strtolower($cSkill);
                    foreach ($cluster as $syn) {
                        $synLower = mb_strtolower($syn);
                        if (mb_strlen($synLower) <= 3) {
                            if ($cLower === $synLower) {
                                return [
                                    'matched' => true,
                                    'type' => 'Padanan Konsep & Sinonim',
                                    'matched_with' => $cSkill,
                                    'status_verifikasi' => 'Sesuai Konsep Industri',
                                ];
                            }
                        } else {
                            if (preg_match('/\b' . preg_quote($synLower, '/') . '\b/iu', $cLower) || 
                                preg_match('/\b' . preg_quote($cLower, '/') . '\b/iu', $synLower)) {
                                return [
                                    'matched' => true,
                                    'type' => 'Padanan Konsep & Sinonim',
                                    'matched_with' => $cSkill,
                                    'status_verifikasi' => 'Sesuai Konsep Industri',
                                ];
                            }
                        }
                    }
                }

                // 2. Fallback: jika tidak ada di daftar skill, cek apakah ada konsep kuat di profil kandidat
                foreach ($cluster as $syn) {
                    $synLower = mb_strtolower($syn);
                    $matchedSyn = false;
                    if (mb_strlen($synLower) <= 3) {
                        if (in_array($synLower, $profileTokens)) {
                            $matchedSyn = true;
                        }
                    } else {
                        if (preg_match('/\b' . preg_quote($synLower, '/') . '\b/iu', $rawProfileText)) {
                            $matchedSyn = true;
                        }
                    }

                    if ($matchedSyn) {
                        return [
                            'matched' => true,
                            'type' => 'Padanan Konsep & Sinonim',
                            'matched_with' => ucwords($syn),
                            'status_verifikasi' => 'Sesuai Konsep Industri',
                        ];
                    }
                }
            }
        }

        // 3. Cek Irisan Token Kata Kunci (Hanya Kata Kunci Substantif Teknis)
        if (!empty($targetTokens)) {
            // Saring hanya token kata kunci substantif (panjang >= 4 karakter dan bukan stop words)
            $substantiveTarget = array_values(array_filter($targetTokens, function ($t) {
                return mb_strlen($t) >= 4 && !in_array($t, $this->stopWords);
            }));

            if (!empty($substantiveTarget)) {
                $targetCount = count($substantiveTarget);

                // 3a. Prioritaskan pengecekan irisan terhadap daftar keahlian eksplisit kandidat
                foreach ($candidateSkillItems as $cSkill) {
                    $cTokens = array_values(array_filter($this->tokenizeText(mb_strtolower($cSkill)), function ($t) {
                        return mb_strlen($t) >= 4 && !in_array($t, $this->stopWords);
                    }));
                    if (!empty($cTokens)) {
                        $commonExplicit = array_values(array_intersect($substantiveTarget, $cTokens));
                        $cRatio = count($commonExplicit) / max(1, $targetCount);
                        // Jika 2+ token substantif cocok dan memenuhi >= 50% requirement atau >= 60% skill kandidat
                        if ((count($commonExplicit) >= 2 && $cRatio >= 0.50) || 
                            (count($commonExplicit) >= 2 && (count($commonExplicit) / count($cTokens)) >= 0.60)) {
                            return [
                                'matched' => true,
                                'type' => 'Padanan Kata Kunci',
                                'matched_with' => $cSkill,
                                'status_verifikasi' => 'Sesuai Keahlian Kandidat',
                            ];
                        }
                    }
                }

                // 3b. Fallback pengecekan terhadap profil umum (CV) - aturan ketat untuk mencegah false positive
                $substantiveProfile = array_values(array_filter($profileTokens, function ($t) {
                    return mb_strlen($t) >= 4 && !in_array($t, $this->stopWords);
                }));

                if (!empty($substantiveProfile)) {
                    $common = array_values(array_intersect($substantiveTarget, $substantiveProfile));
                    $ratio = count($common) / max(1, $targetCount);

                    // Syarat lolos padanan kata kunci teks CV:
                    // Harus minimal 2 token substantif DAN rasio cakupan >= 50% (jika target 2-3 kata)
                    // Atau minimal 3 token substantif DAN rasio cakupan >= 50% (jika target >= 4 kata)
                    $isValidMatch = false;
                    if ($targetCount <= 3 && count($common) >= 2 && $ratio >= 0.50) {
                        $isValidMatch = true;
                    } elseif ($targetCount > 3 && count($common) >= 3 && $ratio >= 0.50) {
                        $isValidMatch = true;
                    }

                    if ($isValidMatch) {
                        return [
                            'matched' => true,
                            'type' => 'Padanan Kata Kunci',
                            'matched_with' => implode(', ', $common),
                            'status_verifikasi' => 'Terpenuhi Sebagian di CV',
                        ];
                    }
                }
            }
        }

        return ['matched' => false];
    }

    /**
     * Ekstraksi otomatis poin kualifikasi/keahlian dari teks deskripsi pekerjaan
     * Menghasilkan daftar kebutuhan riil yang manusiawi dan mengenali 'wajib' vs 'tambahan'
     */
    protected function extractRequirementsFromJobText(string $jobText, string $jobTitle = ''): array
    {
        // 1. Decode HTML entities terlebih dahulu (&gt;, &amp;, dll)
        $text = html_entity_decode($jobText, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // 2. Ganti tag blok HTML (li, p, div, br, h1-h6) dengan newline agar teks butir daftar tidak menyatu
        $text = preg_replace('/<\s*(li|p|div|br\s*\/|h[1-6])(\s+[^>]*)?>/i', "\n", $text);
        $text = preg_replace('/<\s*\/\s*(li|p|div|h[1-6])>/i', "\n", $text);

        // 3. Bersihkan sisa tag HTML
        $clean = strip_tags($text);
        $rawLines = preg_split('/[\r\n]+/', $clean);
        $candidateLines = [];

        // Jika teks berupa satu paragraf narasi panjang tanpa bullet, pecah juga berdasarkan tanda baca kalimat
        foreach ($rawLines as $rl) {
            $t = trim($rl);
            if (mb_strlen($t) < 5) continue;

            // Jika ada tanda bullet/nomor eksplisit di awal (1., 2), -, •), simpan langsung sebagai baris butir
            if (preg_match('/^[\s\-\*\•\d\.\)\(\]]+/u', $t)) {
                $candidateLines[] = $t;
            } elseif (mb_strlen($t) > 75 && (str_contains($t, '. ') || str_contains($t, '; '))) {
                // Pecah kalimat-kalimat narasi
                $sentences = preg_split('/(?<=[.;])\s+/u', $t);
                foreach ($sentences as $s) {
                    $sTrimmed = trim($s);
                    if (mb_strlen($sTrimmed) >= 10) {
                        $candidateLines[] = $sTrimmed;
                    }
                }
            } else {
                $candidateLines[] = $t;
            }
        }

        $reqs = [];
        $seen = [];

        // Kata/frasa non-skill & judul bagian (headings) yang wajib diabaikan
        $ignorePatterns = [
            // Headings English
            'responsibilities', 'responsibility', 'key responsibilities', 'job responsibilities',
            'roles & responsibilities', 'roles and responsibilities', 'duties & responsibilities',
            'duties and responsibilities', 'main duties', 'requirements', 'job requirements',
            'qualifications', 'job qualifications', 'minimum qualifications', 'preferred qualifications',
            'skills & qualifications', 'skills and qualifications', 'what you will do', 'what you\'ll do',
            'who you are', 'about the role', 'about the job', 'job description', 'job summary',
            'job overview', 'overview', 'key accountabilities', 'scope of work',

            // Headings Indonesia
            'tanggung jawab', 'tugas dan tanggung jawab', 'tugas pokok', 'tugas utama', 'uraian tugas',
            'tugas', 'kualifikasi', 'persyaratan', 'kriteria', 'ringkasan', 'deskripsi pekerjaan',
            'ruang lingkup', 'tentang pekerjaan', 'gambaran pekerjaan', 'jobdesk', 'job desk',

            // Syarat Administratif & Ketentuan Kerja
            'siap ditempatkan', 'bersedia ditempatkan', 'area proyek', 'perjalanan dinas',
            'bersedia dinas', 'bersedia lembur', 'bersedia shift', 'bekerja shift',
            'pria', 'wanita', 'laki-laki', 'perempuan', 'usia maksimal', 'usia minimal',
            'pendidikan minimal', 'tamatan minimal', 'lulusan minimal', 'gaji', 'upah',
            'kirim berkas', 'kirim cv', 'surat lamaran', 'portofolio', 'benefit', 'tunjangan',
            'front office berperan krusial', 'perusahaan kami', 'tentang kami', 'lowongan ini',
            'apply now', 'send your cv'
        ];

        // Kata tunggal umum yang DILARANG menjadi requirement mandiri
        $forbiddenSingleWords = [
            'saat', 'tamu', 'datang', 'check', 'in', 'out', 'pergi', 'bisa', 'ada', 'yang', 'dan', 'atau',
            'untuk', 'dengan', 'serta', 'pada', 'dari', 'ke', 'ini', 'itu', 'adalah', 'kami', 'anda',
            'dalam', 'sangat', 'baik', 'memiliki', 'mampu', 'dapat', 'harus', 'wajib', 'posisi'
        ];

        foreach ($candidateLines as $line) {
            $rawLine = trim($line);
            if (mb_strlen($rawLine) < 5) continue;

            // Bersihkan nomor urut atau simbol bullet di awal: 1., 2), -, *, •
            $cleanedLine = trim(preg_replace('/^[\s\-\*\•\d\.\)\(\]]+/u', '', $rawLine));
            $lower = mb_strtolower($cleanedLine);

            if (mb_strlen($cleanedLine) < 6 || mb_strlen($cleanedLine) > 200) {
                continue;
            }

            // 1. Cek apakah baris ini judul bagian / heading (misal: "Responsibilities :", "Job Description:", "Persyaratan:")
            $strippedColon = trim(rtrim($cleanedLine, ":- \t\n\r"));
            $strippedLower = mb_strtolower($strippedColon);
            $colonWordCount = count(preg_split('/\s+/u', $strippedColon));

            // Jika baris berakhiran titik dua (:) dan relatif pendek (<= 6 kata), ini pasti heading sub-judul
            if (preg_match('/:\s*$/u', $cleanedLine) && $colonWordCount <= 6) {
                continue;
            }

            // Jika teksnya murni judul header kualifikasi / deskripsi
            $knownHeadingTitles = [
                'responsibilities', 'responsibility', 'key responsibilities', 'job responsibilities',
                'roles and responsibilities', 'roles & responsibilities', 'duties and responsibilities',
                'duties & responsibilities', 'main duties', 'requirements', 'job requirements',
                'minimum requirements', 'qualifications', 'job qualifications', 'minimum qualifications',
                'preferred qualifications', 'skills & qualifications', 'skills and qualifications',
                'what you will do', 'what you will be doing', 'what you\'ll do', 'who you are',
                'about the role', 'about the job', 'about us', 'about the company', 'job description',
                'job summary', 'overview', 'job overview', 'scope of work', 'key accountabilities',
                'tanggung jawab', 'tugas dan tanggung jawab', 'tugas utama', 'tugas pokok', 'uraian tugas',
                'tugas', 'kualifikasi', 'persyaratan', 'kriteria', 'ringkasan', 'deskripsi pekerjaan',
                'gambaran pekerjaan', 'ruang lingkup pekerjaan', 'jobdesk', 'job desk'
            ];
            if (in_array($strippedLower, $knownHeadingTitles)) {
                continue;
            }

            // Cek apakah baris ini instruksi administratif atau deskripsi umum perusahaan
            $isIgnored = false;
            foreach ($ignorePatterns as $pattern) {
                if (str_starts_with($lower, $pattern) || str_contains($lower, $pattern . ':') || $lower === $pattern || str_starts_with($strippedLower, $pattern)) {
                    $isIgnored = true;
                    break;
                }
            }
            if ($isIgnored) continue;

            // Deteksi tipe keahlian (wajib vs tambahan/nilai plus)
            $tipeKeahlian = 'wajib';
            if (
                str_contains($lower, 'nilai tambah') ||
                str_contains($lower, 'nilai plus') ||
                str_contains($lower, 'diutamakan') ||
                str_contains($lower, 'keuntungan') ||
                str_contains($lower, 'preferable') ||
                str_contains($lower, 'opsional')
            ) {
                $tipeKeahlian = 'tambahan';
            }

            $formattedTitle = $this->cleanRequirementTitle($cleanedLine);
            $titleLower = mb_strtolower($formattedTitle);

            // Validasi kualitas: pastikan minimal 2 kata ATAU istilah teknis domain yang valid
            $wordCount = count(preg_split('/\s+/u', trim($formattedTitle)));
            if ($wordCount < 2 && in_array($titleLower, $forbiddenSingleWords)) {
                continue;
            }
            if ($wordCount < 2 && mb_strlen($formattedTitle) < 9) {
                continue;
            }

            if (mb_strlen($formattedTitle) >= 6 && !isset($seen[$titleLower])) {
                $seen[$titleLower] = true;
                $reqs[] = [
                    'title' => $formattedTitle,
                    'raw_text' => $cleanedLine,
                    'tipe_keahlian' => $tipeKeahlian,
                    'level_kemahiran' => 'menengah',
                ];
            }
        }

        return $reqs;
    }

    /**
     * Sintesis kompetensi standar profesional industri berdasarkan judul formasi pekerjaan
     * Universal Fallback: Menjamin SEMUA lowongan kerja memiliki rincian kompetensi yang profesional dan masuk akal
     */
    protected function synthesizeCompetenciesFromJobTitle(string $jobTitle, string $contextText = ''): array
    {
        $cleanTitle = trim($jobTitle);
        $t = mb_strtolower($cleanTitle . ' ' . $contextText);

        // 1. Front Office / Resepsionis / Perhotelan / Tamu
        if (str_contains($t, 'front office') || str_contains($t, 'resepsionis') || str_contains($t, 'receptionist') || str_contains($t, 'frontliner') || str_contains($t, 'guest relation') || str_contains($t, 'concierge')) {
            return [
                ['title' => 'Pelayanan Tamu & Hospitality', 'tipe_keahlian' => 'wajib'],
                ['title' => 'Komunikasi & Interaksi Pelanggan', 'tipe_keahlian' => 'wajib'],
                ['title' => 'Prosedur Check-in & Administrasi Tamu', 'tipe_keahlian' => 'wajib'],
                ['title' => 'Penanganan Informasi & Kepuasan Tamu', 'tipe_keahlian' => 'tambahan'],
            ];
        }

        // 2. Kasir / Cashier / Teller
        if (str_contains($t, 'kasir') || str_contains($t, 'cashier') || str_contains($t, 'teller')) {
            return [
                ['title' => 'Pengelolaan Transaksi Kasir & Sistem POS', 'tipe_keahlian' => 'wajib'],
                ['title' => 'Ketelitian Hitung & Rekonsiliasi Kas', 'tipe_keahlian' => 'wajib'],
                ['title' => 'Pelayanan Pelanggan (Customer Service)', 'tipe_keahlian' => 'wajib'],
                ['title' => 'Pencatatan & Pelaporan Keuangan Harian', 'tipe_keahlian' => 'tambahan'],
            ];
        }

        // 3. Administrasi / Admin / Tata Usaha / Staff Kantor / Sekretaris
        if (str_contains($t, 'admin') || str_contains($t, 'administrasi') || str_contains($t, 'tata usaha') || str_contains($t, 'sekretaris') || str_contains($t, 'clerical')) {
            return [
                ['title' => 'Administrasi Dokumen & Pengarsipan', 'tipe_keahlian' => 'wajib'],
                ['title' => 'Pengoperasian Komputer & Aplikasi Perkantoran (MS Office/Excel)', 'tipe_keahlian' => 'wajib'],
                ['title' => 'Ketelitian Pengolahan Data & Korespondensi', 'tipe_keahlian' => 'wajib'],
                ['title' => 'Komunikasi Internal & Antar Tim', 'tipe_keahlian' => 'tambahan'],
            ];
        }

        // 4. Sales / Marketing / Pemasaran / Penjualan / Promosi / SPG / SPM
        if (str_contains($t, 'sales') || str_contains($t, 'marketing') || str_contains($t, 'pemasaran') || str_contains($t, 'penjualan') || str_contains($t, 'spg') || str_contains($t, 'spm') || str_contains($t, 'canvass') || str_contains($t, 'telemarketing')) {
            return [
                ['title' => 'Teknik Penjualan & Promosi Produk', 'tipe_keahlian' => 'wajib'],
                ['title' => 'Komunikasi Persuasif & Negosiasi', 'tipe_keahlian' => 'wajib'],
                ['title' => 'Orientasi Pencapaian Target Penjualan', 'tipe_keahlian' => 'wajib'],
                ['title' => 'Pengelolaan Hubungan Pelanggan (CRM)', 'tipe_keahlian' => 'tambahan'],
            ];
        }

        // 5. Customer Service / Layanan Pelanggan / Call Center
        if (str_contains($t, 'customer service') || str_contains($t, 'layanan pelanggan') || str_contains($t, 'call center') || str_contains($t, 'helpdesk') || str_contains($t, 'cs')) {
            return [
                ['title' => 'Komunikasi & Pelayanan Pelanggan', 'tipe_keahlian' => 'wajib'],
                ['title' => 'Penyelesaian Masalah & Keluhan (Problem Solving)', 'tipe_keahlian' => 'wajib'],
                ['title' => 'Pemberian Informasi Produk & Layanan', 'tipe_keahlian' => 'wajib'],
                ['title' => 'Pencatatan Log & Feedback Pelanggan', 'tipe_keahlian' => 'tambahan'],
            ];
        }

        // 6. Driver / Supir / Pengemudi / Kurir / Delivery
        if (str_contains($t, 'driver') || str_contains($t, 'supir') || str_contains($t, 'sopir') || str_contains($t, 'pengemudi') || str_contains($t, 'kurir') || str_contains($t, 'delivery')) {
            return [
                ['title' => 'Keterampilan Mengemudi & Keselamatan Berkendara', 'tipe_keahlian' => 'wajib'],
                ['title' => 'Pemahaman Rute & Ketepatan Waktu Pengiriman', 'tipe_keahlian' => 'wajib'],
                ['title' => 'Perawatan & Pemeriksaan Kendaraan Berkala', 'tipe_keahlian' => 'wajib'],
                ['title' => 'Pelayanan & Komunikasi Pengantaran Barang', 'tipe_keahlian' => 'tambahan'],
            ];
        }

        // 7. Gudang / Warehouse / Logistik / Inventory / Checker
        if (str_contains($t, 'gudang') || str_contains($t, 'warehouse') || str_contains($t, 'logistik') || str_contains($t, 'inventory') || str_contains($t, 'stok') || str_contains($t, 'checker')) {
            return [
                ['title' => 'Pengelolaan & Pengecekan Stok Barang', 'tipe_keahlian' => 'wajib'],
                ['title' => 'Ketelitian Bongkar Muat & Penataan Gudang', 'tipe_keahlian' => 'wajib'],
                ['title' => 'Pencatatan Mutasi Barang (Inventory Control)', 'tipe_keahlian' => 'wajib'],
                ['title' => 'Standar K3 & Keselamatan Kerja Pergudangan', 'tipe_keahlian' => 'tambahan'],
            ];
        }

        // 8. IT / Programmer / Developer / Software / Web
        if (str_contains($t, 'developer') || str_contains($t, 'programmer') || str_contains($t, 'software') || str_contains($t, 'web') || str_contains($t, 'frontend') || str_contains($t, 'backend') || str_contains($t, 'fullstack') || str_contains($t, 'it support')) {
            return [
                ['title' => 'Pemrograman Komputer & Logika Algoritma', 'tipe_keahlian' => 'wajib'],
                ['title' => 'Pengembangan & Pemeliharaan Aplikasi/Sistem', 'tipe_keahlian' => 'wajib'],
                ['title' => 'Analisis Kebutuhan & Problem Solving Teknis', 'tipe_keahlian' => 'wajib'],
                ['title' => 'Pengujian & Dokumentasi Sistem Informasi', 'tipe_keahlian' => 'tambahan'],
            ];
        }

        // 9. Project Manager / Koordinator Proyek
        if (str_contains($t, 'project manager') || str_contains($t, 'manajer proyek') || str_contains($t, 'koordinator')) {
            return [
                ['title' => 'Manajemen Proyek & Timeline Eksekusi', 'tipe_keahlian' => 'wajib'],
                ['title' => 'Koordinasi & Komunikasi Stakeholders', 'tipe_keahlian' => 'wajib'],
                ['title' => 'Monitoring Progress & Mitigasi Risiko Proyek', 'tipe_keahlian' => 'wajib'],
                ['title' => 'Dokumentasi & Pelaporan Proyek Berkala', 'tipe_keahlian' => 'tambahan'],
            ];
        }

        // 10. Desain Grafis / Graphic Designer / Kreatif / Editor
        if (str_contains($t, 'desain') || str_contains($t, 'designer') || str_contains($t, 'kreatif') || str_contains($t, 'editor') || str_contains($t, 'video')) {
            return [
                ['title' => 'Desain Grafis & Aplikasi Kreatif', 'tipe_keahlian' => 'wajib'],
                ['title' => 'Kreativitas Visual & Tipografi', 'tipe_keahlian' => 'wajib'],
                ['title' => 'Pengembangan Aset Konten Visual', 'tipe_keahlian' => 'wajib'],
                ['title' => 'Manajemen Revisi & Deadline Desain', 'tipe_keahlian' => 'tambahan'],
            ];
        }

        // 11. Akuntansi / Finance / Keuangan / Pajak / Auditor
        if (str_contains($t, 'akuntan') || str_contains($t, 'finance') || str_contains($t, 'keuangan') || str_contains($t, 'pajak') || str_contains($t, 'accounting')) {
            return [
                ['title' => 'Pencatatan Transaksi & Pembukuan Keuangan', 'tipe_keahlian' => 'wajib'],
                ['title' => 'Penyusunan Laporan Keuangan & Rekonsiliasi', 'tipe_keahlian' => 'wajib'],
                ['title' => 'Ketelitian Pengolahan Data Akuntansi', 'tipe_keahlian' => 'wajib'],
                ['title' => 'Kepatuhan Pajak & Administrasi Keuangan', 'tipe_keahlian' => 'tambahan'],
            ];
        }

        // 12. Koki / Cook / Chef / Barista / Waiter / F&B
        if (str_contains($t, 'koki') || str_contains($t, 'chef') || str_contains($t, 'cook') || str_contains($t, 'barista') || str_contains($t, 'waiter') || str_contains($t, 'waitress') || str_contains($t, 'f&b') || str_contains($t, 'restoran')) {
            return [
                ['title' => 'Persiapan & Pengolahan Makanan/Minuman', 'tipe_keahlian' => 'wajib'],
                ['title' => 'Standar Higienitas, Sanitasi & Kebersihan F&B', 'tipe_keahlian' => 'wajib'],
                ['title' => 'Pelayanan Cepat & Ketepatan Pesanan Pelanggan', 'tipe_keahlian' => 'wajib'],
                ['title' => 'Manajemen Waktu & Kerjasama Tim', 'tipe_keahlian' => 'tambahan'],
            ];
        }

        // 13. Satpam / Keamanan / Security
        if (str_contains($t, 'satpam') || str_contains($t, 'keamanan') || str_contains($t, 'security')) {
            return [
                ['title' => 'Pengawasan & Penjagaan Keamanan Lingkungan', 'tipe_keahlian' => 'wajib'],
                ['title' => 'Penerapan Prosedur SOP Keamanan & Tanggap Darurat', 'tipe_keahlian' => 'wajib'],
                ['title' => 'Pelayanan & Pemeriksaan Tamu/Pengunjung', 'tipe_keahlian' => 'wajib'],
                ['title' => 'Pencatatan Buku Log & Patroli Area', 'tipe_keahlian' => 'tambahan'],
            ];
        }

        // 14. Operator Produksi / Teknisi / Mekanik
        if (str_contains($t, 'operator') || str_contains($t, 'produksi') || str_contains($t, 'teknisi') || str_contains($t, 'mekanik')) {
            return [
                ['title' => 'Pengoperasian Mesin & Alat Kerja Sesuai SOP', 'tipe_keahlian' => 'wajib'],
                ['title' => 'Pemeriksaan Kualitas (Quality Control) & Ketelitian', 'tipe_keahlian' => 'wajib'],
                ['title' => 'Penerapan Keselamatan dan Kesehatan Kerja (K3)', 'tipe_keahlian' => 'wajib'],
                ['title' => 'Pemeliharaan & Troubleshooting Peralatan Kerja', 'tipe_keahlian' => 'tambahan'],
            ];
        }

        // 15. Universal Default untuk Profesi Lainnya
        $roleTitle = !empty($cleanTitle) ? ucwords(mb_strtolower($cleanTitle)) : 'Tugas Pokok Formasi';
        return [
            ['title' => 'Kompetensi Fungsional: ' . $roleTitle, 'tipe_keahlian' => 'wajib'],
            ['title' => 'Komunikasi Efektif & Koordinasi Tim', 'tipe_keahlian' => 'wajib'],
            ['title' => 'Manajemen Waktu & Ketelitian Kerja', 'tipe_keahlian' => 'wajib'],
            ['title' => 'Kepatuhan Standar Operasional Prosedur (SOP)', 'tipe_keahlian' => 'tambahan'],
        ];
    }

    /**
     * Meringkas dan memurnikan baris persyaratan lowongan menjadi judul keahlian/kompetensi yang to-the-point
     * Bilingual (Bahasa Indonesia & Bahasa Inggris): Mengubah kalimat narasi panjang menjadi nama keahlian ringkas 2-7 kata
     */
    protected function cleanRequirementTitle(string $line): string
    {
        $clean = trim($line);

        // 1. Pangkas awalan kata kerja operasional, klise deskripsi, dan pengantar bertele-tele (ID & EN)
        $prefixPatterns = [
            // Pola Bahasa Indonesia
            '/^(tetap\s+terinformasi\s+(tentang|mengenai)\s+|terinformasi\s+(tentang|mengenai)\s+)/ui',
            '/^(melakukan\s+aktivitas\s+|melaksanakan\s+aktivitas\s+|menjalankan\s+aktivitas\s+|melakukan\s+kegiatan\s+|melaksanakan\s+kegiatan\s+)/ui',
            '/^(melakukan\s+|melaksanakan\s+|menjalankan\s+|mengerjakan\s+|mengatur\s+|mengelola\s+|menangani\s+|memelihara\s+)/ui',
            '/^(bertanggung\s+jawab\s+(untuk|atas|dalam|terhadap)\s+|bertanggung\s+jawab\s+)/ui',
            '/^(memastikan\s+(kepatuhan|kelancaran|tercapainya|akurasi)?\s*(terhadap|pada|dalam)?\s*)/ui',
            '/^(memiliki\s+kemampuan\s+(dalam|untuk)?\s*|memiliki\s+kapasitas\s+(dalam|untuk)?\s*|memiliki\s+keahlian\s+(dalam|untuk)?\s*|memiliki\s+pengalaman\s+(dalam|sebagai)?\s*|memiliki\s+)/ui',
            '/^(mampu\s+bekerja\s+secara\s+|mampu\s+melakukan\s+|mampu\s+mengawasi\s+|mampu\s+mengoperasikan\s+|mampu\s+|dapat\s+|terbiasa\s+|bisa\s+)/ui',
            '/^(memahami\s+alur\s+|memahami\s+konsep\s+|memahami\s+|menguasai\s+)/ui',
            '/^(berpengalaman\s+(?:>\s*\d+\s*tahun\s+)?(?:minimal\s*\d+\s*tahun\s+)?sebagai\s+|berpengalaman\s+sebagai\s+|pengalaman\s+sebagai\s+)/ui',
            '/^(wajib\s+|harus\s+|bersedia\s+)/ui',

            // Pola Bahasa Inggris (English Prefixes)
            '/^(regularly\s+|actively\s+|routinely\s+|frequently\s+|periodically\s+|consistently\s+|effectively\s+|efficiently\s+|closely\s+|continuously\s+|diligently\s+|promptly\s+|strictly\s+|accurately\s+|professionally\s+|proactively\s+|successfully\s+|independently\s+|directly\s+|seamlessly\s+|properly\s+)/ui',
            '/^(secara\s+(rutin|berkala|aktif|intensif|efektif|efisien|konsisten|mandiri|profesional|akurat|langsung|tepat)\s+)/ui',
            '/^(prepare\s+|preparing\s+|handle\s+|handling\s+|manage\s+|managing\s+|coordinate\s+|coordinating\s+|collaborate\s+(with)?\s*|collaborating\s+(with)?\s*|optimize\s+|optimizing\s+|monitor\s+|monitoring\s+|ensure\s+|ensuring\s+|adhere\s+(to)?\s*|maintain\s+|maintaining\s+)/ui',
            '/^(drive\s+|driving\s+|lead\s+(?!generation)|leading\s+|oversee\s+|overseeing\s+|supervise\s+|supervising\s+|support\s+|supporting\s+|provide\s+|providing\s+|build\s+|building\s+|create\s+|creating\s+|develop\s+|developing\s+|design\s+(and\s+develop)?\s*|designing\s+|draft\s+(and\s+review)?\s*|drafting\s+|review\s+|reviewing\s+|implement\s+|implementing\s+|execute\s+|executing\s+|deliver\s+|delivering\s+|evaluate\s+|evaluating\s+|facilitate\s+|facilitating\s+|conduct\s+|conducting\s+|track\s+|tracking\s+|analyze\s+|analyzing\s+)/ui',
            '/^(menyiapkan\s+|membuat\s+|mengelola\s+|mengkoordinasikan\s+|mengoptimalkan\s+|memantau\s+|memonitor\s+|mematuhi\s+|menjaga\s+|memimpin\s+|mengawasi\s+|mendukung\s+|membantu\s+|merancang\s+|mengembangkan\s+|membangun\s+|menyusun\s+|meninjau\s+|menerapkan\s+|mengeksekusi\s+|memberikan\s+|memfasilitasi\s+|menganalisis\s+|mengevaluasi\s+)/ui',
            '/^(based\s+on\s+.*?\s+(for|in|to)\s+|based\s+on\s+)/ui',
            '/^(stay\s+informed\s+(about|on)\s+|keep\s+updated\s+(about|on)\s+)/ui',
            '/^(responsible\s+for\s+(managing|handling|overseeing|leading|executing|performing)?\s*)/ui',
            '/^(proven\s+experience\s+(in|as|with)?\s*|hands-on\s+experience\s+(in|with)?\s*|experience\s+(in|as|with)?\s*)/ui',
            '/^(be\s+able\s+to\s+(follow|keep\s+up\s+with|monitor|track|understand|adopt|perform|execute|handle|manage|work|lead|deliver)?\s*|ability\s+to\s+(perform|execute|handle|manage|work|lead|deliver)?\s*|able\s+to\s+)/ui',
            '/^(follow\s+(the\s+)?|tracking\s+(the\s+)?|monitoring\s+(the\s+)?)/ui',
            '/^(proficient\s+(in|with)\s+|expert\s+in\s+|skilled\s+in\s+)/ui',
            '/^(strong\s+knowledge\s+of\s+|good\s+knowledge\s+of\s+|in-depth\s+knowledge\s+of\s+|deep\s+understanding\s+of\s+|understanding\s+of\s+)/ui',
            '/^(must\s+have\s+(strong|solid|proven)?\s*|should\s+have\s+(strong|solid|proven)?\s*|must\s+possess\s+|possess\s+)/ui',
            '/^(performing\s+|executing\s+|conducting\s+|overseeing\s+)/ui',
            '/^(perform\s+|execute\s+|conduct\s+|oversee\s+|assist\s+(in|with)?\s*)/ui',
            '/^(familiar\s+with\s+|familiarity\s+with\s+|knowledge\s+of\s+)/ui',
            '/^(demonstrated\s+skills\s+in\s+|skills\s+in\s+)/ui',
            '/^(the\s+|an?\s+)/ui',
        ];
        for ($pass = 0; $pass < 3; $pass++) {
            $prevClean = $clean;
            foreach ($prefixPatterns as $p) {
                $clean = preg_replace($p, '', $clean);
            }
            if ($clean === $prevClean) {
                break;
            }
        }

        // 2. Tangani tanda kurung terlebih dahulu sebelum suffix pattern memotongnya
        $clean = preg_replace_callback('/\(([^)]+)\)/u', function($m) {
            $inside = trim($m[1]);
            $insideLower = mb_strtolower($inside);

            // Buang catatan lokasi, waktu, media, atau shift kerja
            if (preg_match('/(on-site|remote|remotely|wfh|wfo|hybrid|online|offline|daring|luring|shift|daily|weekly|monthly|24\/7|call|email|telepon)/ui', $inside)) {
                return '';
            }
            if (str_contains($insideLower, 'sistem alur kerja') || str_contains($insideLower, 'workflow system') || str_contains($insideLower, 'aturan internal') || str_contains($insideLower, 'internal company') || mb_strlen($inside) > 40) {
                if (preg_match('/(pemeriksaan dokumen|inspeksi dokumen|audit dokumen|document inspection|document audit)/ui', $inside, $subMatch)) {
                    return '& ' . ucwords($subMatch[1]);
                }
                return ''; // Buang rincian operasional internal yang berbelit-belit
            }
            // Bersihkan awalan "misalnya, ", "contoh: ", "aturan ", "e.g., ", "such as "
            $inside = preg_replace('/^(misalnya,?\s*|contoh:?\s*|aturan\s+|e\.?g\.?,?\s*|such\s+as\s+|for\s+example,?\s*)/ui', '', $inside);
            $inside = preg_replace('/\b(peraturan|rules)\s+/ui', '', $inside);
            if (mb_strlen($inside) <= 30) {
                return '(' . trim($inside) . ')';
            }
            return '';
        }, $clean);

        // 3. Pangkas keterangan konteks internal/eksternal perusahaan di belakang (ID & EN)
        $suffixPatterns = [
            // Keterangan standar teknis / regulasi di belakang
            '/\s*(in\s+accordance\s+with|according\s+to)\s+(ifrs|gaap|psak|iso|sop|standard).*$/ui',
            '/\s*sesuai\s+(dengan\s+)?(standar|pedoman|aturan|ifrs|psak|iso).*$/ui',
            // Keterangan tim atau lingkup
            '/\s*with\s+(global|local|internal|external|cross-functional)\s+teams?.*$/ui',
            '/\s*(melalui|through)\s+(fifo|lifo|metode|sistem|stock\s+counts?).*$/ui',
            '/\s*to\s+(prevent|avoid|minimize|eliminate)\s+.*$/ui',
            '/\s*in\s+(plant|factory|office|warehouse)\s+environment.*$/ui',

            // Suffix Indonesia
            '/\s*(menggunakan|melalui)\s+sistem\s+alur\s+kerja\s+internal\s*.*$/ui',
            '/\s*sesuai\s+(dengan\s+)?(aturan|kebijakan|standar|prosedur|sop)\s+internal\s+perusahaan.*$/ui',
            '/\s*sesuai\s+(dengan\s+)?(aturan|kebijakan|standar|prosedur|sop)\s+(yang\s+berlaku|perusahaan).*$/ui',
            '/\s*di\s+dalam\s+organisasi\s+perusahaan.*$/ui',
            '/\s*untuk\s+(kebutuhan|kepentingan|tujuan)\s+perusahaan.*$/ui',
            '/\s*demi\s+(kelancaran|mendukung)\s+operasional.*$/ui',
            '/\s*dan\s+berbagi\s+informasi\s+terbaru.*$/ui',
            '/\s*serta\s+melaporkannya\s+kepada\s+.*$/ui',
            '/\s*(menjadi|sebagai)?\s*(nilai\s+tambah|nilai\s+plus|nilai\s+keuntungan)\s*$/ui',
            '/\s+yang\s+(sangat\s+)?(baik|unggul|efisien|disiplin|akurat)\s*$/ui',
            '/\s*(dengan\s+)?(baik\s+dan\s+benar|secara\s+profesional|tepat\s+waktu)\s*$/ui',

            // Keterangan tujuan / klausa subordinatif berlebih (ID & EN)
            '/\s*,?\s+dalam\s+upaya\s+.*$/ui',
            '/\s*,?\s+agar\s+(bisa|dapat|mampu|berjalan|mencapai)\s+.*$/ui',
            '/\s*,?\s+untuk\s+(mencapai|mengarahkan|meningkatkan|mendukung|memaksimalkan|membantu|menghasilkan)\s+.*$/ui',
            '/\s*,?\s+guna\s+(mencapai|meningkatkan|mendukung|memastikan)\s+.*$/ui',

            // Suffix English
            '/\s*,?\s+in\s+order\s+to\s+.*$/ui',
            '/\s*,?\s+so\s+that\s+.*$/ui',
            '/\s*,?\s+to\s+(achieve|reach|boost|support|drive|ensure|deliver)\s+.*$/ui',
            '/\s*(in\s+accordance\s+with|according\s+to)\s+company\s+(rules|standards|policies|guidelines|regulations).*$/ui',
            '/\s*using\s+internal\s+(workflow|company)\s+systems?.*$/ui',
            '/\s*within\s+the\s+company\s+(organization|group).*$/ui',
            '/\s*for\s+company\s+(operational|business)\s+needs.*$/ui',
            '/\s*to\s+ensure\s+smooth\s+operations?.*$/ui',
            '/\s*and\s+share\s+(the\s+)?latest\s+information.*$/ui',
            '/\s*and\s+report\s+(it\s+)?to\s+(management|supervisor).*$/ui',
            '/\s*(is\s+a\s+)?(plus|bonus|an\s+advantage|preferred)\s*$/ui',
            '/\s+in\s+a\s+timely\s+(and\s+professional\s+)?manner\s*$/ui',
            '/\s*(with\s+)?high\s+accuracy\s*$/ui',
            '/\s*for\s+brand\s+awareness.*$/ui',
            '/\s*with\s+business\s+partners?.*$/ui',
            '/\s*in\s+(a\s+)?fast-paced\s+market.*$/ui',
            '/\s*,?\s+etc\.?\s*$/ui',
        ];
        foreach ($suffixPatterns as $s) {
            $clean = preg_replace($s, '', $clean);
        }

        // 4. Pangkas pelengkap komplementer yang bukan inti skill (ID & EN)
        $clean = preg_replace('/\s+untuk\s+(barang|produk|jasa|layanan|kebutuhan)\s+.*$/ui', '', $clean);
        $clean = preg_replace('/\s+for\s+(goods|products|services|business\s+schemes)\s+.*$/ui', '', $clean);

        // 5. Potong klausa koordinatif kedua jika masih terlalu panjang
        if (mb_strlen($clean) > 45) {
            $parts = preg_split('/\s+(?:dan|serta|and)\s+(?:berbagi|berkoordinasi|menjalin|memastikan|melaporkan|share|collaborate|coordinate|ensure|report)\s+/ui', $clean);
            if (!empty($parts[0]) && mb_strlen(trim($parts[0])) >= 8) {
                $clean = trim($parts[0]);
            }
        }

        // 6. Normalisasi kata pemanis dan spasi
        $clean = preg_replace('/\b(terbaru|terkini|latest|current)\b/ui', '', $clean);
        $clean = preg_replace('/\s+/', ' ', $clean);
        $clean = trim($clean, " \t\n\r\0\x0B-.,;()&");

        // Tutup tanda kurung jika terbuka
        if (str_contains($clean, '(') && !str_contains($clean, ')')) {
            $clean .= ')';
        }

        // 7. Jika hasilnya terlalu pendek (< 4 huruf), kembalikan baris asal yang dipangkas
        if (mb_strlen($clean) < 4) {
            $clean = trim($line);
        }

        // 8. Kapitalisasi awal kata judul secara profesional (Bilingual ID/EN)
        $lowercaseWords = [
            'dan', 'atau', 'di', 'ke', 'dari', 'yang', 'untuk', 'pada', 'dengan', 'serta',
            'and', 'or', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'as', 'the', 'a', 'an'
        ];
        $words = preg_split('/\s+/u', $clean);
        $titleWords = [];
        foreach ($words as $idx => $w) {
            $wLower = mb_strtolower($w);
            if ($idx > 0 && in_array($wLower, $lowercaseWords)) {
                $titleWords[] = $wLower;
            } else {
                $titleWords[] = mb_convert_case(mb_substr($w, 0, 1), MB_CASE_UPPER) . mb_substr($w, 1);
            }
        }

        return implode(' ', $titleWords);
    }

    /**
     * Dimensi 3: Evaluasi Kesesuaian Pendidikan & Bidang Studi (Bobot 20%)
     */
    protected function evaluateEducationAndFieldMatch(JobSeeker $candidate, object $job): array
    {
        $candidateEdu = $candidate->educationLevel ?? $candidate->pendidikan ?? null;
        $eduLevelMatch = $this->evaluateEducationMatch($candidateEdu, $job->educationLevel);

        // Jika jenjang pendidikan di bawah syarat minimal, skor dimensi pendidikan adalah 0%
        if (!$eduLevelMatch['is_matched']) {
            return [
                'score' => 0,
                'education_level_match' => $eduLevelMatch,
                'field_score' => 0,
            ];
        }

        $levelScore = $eduLevelMatch['score'];

        // Kesesuaian Jurusan
        $jobField = trim($job->jurusan_studi ?? '');
        $candField = trim($candidate->jurusan ?? '');

        if ($jobField === '' || mb_strtolower($jobField) === 'semua jurusan' || mb_strtolower($jobField) === 'semua') {
            $fieldScore = 100;
        } elseif ($candField === '') {
            $fieldScore = 50;
        } else {
            $sim = $this->calculateTextSimilarity($jobField, $candField);
            if (str_contains(mb_strtolower($candField), mb_strtolower($jobField)) || str_contains(mb_strtolower($jobField), mb_strtolower($candField))) {
                $fieldScore = 100;
            } elseif ($sim >= 0.5) {
                $fieldScore = 85;
            } else {
                $fieldScore = 40;
            }
        }

        $compositeEduScore = (int) round((0.6 * $levelScore) + (0.4 * $fieldScore));

        return [
            'score' => $compositeEduScore,
            'education_level_match' => $eduLevelMatch,
            'field_score' => $fieldScore,
        ];
    }

    /**
     * Dimensi 4: Evaluasi Kesesuaian Pengalaman Kerja (Bobot 10%)
     */
    protected function evaluateExperienceMatch(JobSeeker $candidate, object $job): array
    {
        $reqYears = $job->pengalaman_minimal_tahun ?? 0;

        // Ekstraksi kebutuhan pengalaman dari deskripsi jika belum terdata di kolom khusus
        if ($reqYears <= 0 && !empty($job->deskripsi_pekerjaan)) {
            if (preg_match('/(?:pengalaman\s*(?:kerja\s*)?(?:minimal|min\.?)\s*(\d+)\s*tahun|(?:minimal|min\.?)\s*(\d+)\s*tahun\s*(?:pengalaman|bekerja))/i', $job->deskripsi_pekerjaan, $m)) {
                $reqYears = (int) (!empty($m[1]) ? $m[1] : (!empty($m[2]) ? $m[2] : 0));
            }
        }

        if ($reqYears <= 0) {
            return ['score' => 100, 'label' => 'Terbuka Fresh Graduate'];
        }

        // Estimasi tahun pengalaman kandidat
        $candYears = 0;
        $expText = $candidate->experience ?? '';

        if (!empty($candidate->experience_range)) {
            $candYears = match ($candidate->experience_range) {
                'fresh_graduate' => 0,
                '<1' => 0.5,
                '1-3' => 2,
                '3-5' => 4,
                '>5' => 6,
                default => 1,
            };
        } elseif (!empty($expText)) {
            // Hitung jumlah baris/record riwayat kerja
            $count = substr_count($expText, ';');
            $candYears = max(1, $count);
        }

        if ($candYears >= $reqYears) {
            $score = 100;
        } else {
            $gap = $reqYears - $candYears;
            $score = max(0, (int) round(100 - ($gap * 25)));
        }

        return [
            'score' => $score,
            'required_years' => $reqYears,
            'candidate_years' => $candYears,
        ];
    }

    /**
     * Dimensi 5: Evaluasi Kesesuaian Lokasi & Sistem Kerja (Bobot 5%)
     */
    protected function evaluateLocationMatch(JobSeeker $candidate, object $job): array
    {
        $workSystem = mb_strtolower($job->sistem_kerja ?? '');

        // Jika WFH atau Remote, lokasi tidak membatasi
        if ($workSystem === 'remote' || $workSystem === 'wfh') {
            return ['score' => 100, 'label' => 'Fleksibel (Remote/WFH)'];
        }

        $cReg = $candidate->regency_id ? trim((string) $candidate->regency_id) : null;
        $jReg = $job->regency_id ? trim((string) $job->regency_id) : null;

        if ($cReg && $jReg && $cReg === $jReg) {
            return ['score' => 100, 'label' => 'Kabupaten/Kota Sama'];
        }

        $cProv = $candidate->province_id ? trim((string) $candidate->province_id) : null;
        $jProv = $job->provinsi_id ? trim((string) $job->provinsi_id) : null;

        if ($cProv && $jProv && $cProv === $jProv) {
            return ['score' => 70, 'label' => 'Provinsi Sama'];
        }

        // Fallback teks jika regency_id/province_id kosong tapi nama kota/provinsi ada
        $candKab = mb_strtolower(trim($candidate->kab_kota ?? ''));
        $candProv = mb_strtolower(trim($candidate->provinsi ?? ''));
        $jobRegion = mb_strtolower(trim(($job->region_pembeker ?? '') . ' ' . ($job->reg ?? '')));

        if ($candKab !== '' && str_contains($jobRegion, $candKab)) {
            return ['score' => 100, 'label' => 'Kabupaten/Kota Sama (Cocok Alamat)'];
        }

        if ($candProv !== '' && str_contains($jobRegion, $candProv)) {
            return ['score' => 70, 'label' => 'Provinsi Sama (Cocok Alamat)'];
        }

        return ['score' => 0, 'label' => 'Luar Wilayah'];
    }

    /**
     * Evaluasi kesesuaian tingkat pendidikan antara kandidat dan lowongan kerja
     */
    public function evaluateEducationMatch($candidateEdu, $jobEdu): array
    {
        // Parsing level pencaker jika berupa string (fallback dari kolom req_pk_pencaker.pendidikan)
        $cLevel = null;
        $cName = '-';

        if (is_object($candidateEdu)) {
            $cLevel = $candidateEdu->sort_order ?? $candidateEdu->id ?? null;
            $cName = $candidateEdu->name ?? '-';
        } elseif (is_numeric($candidateEdu)) {
            $cLevel = (int) $candidateEdu;
        } elseif (is_string($candidateEdu) && trim($candidateEdu) !== '') {
            $cName = trim($candidateEdu);
            $cStr = mb_strtolower($cName);
            $cLevel = match (true) {
                str_contains($cStr, 's3') || str_contains($cStr, 'doktor') => 7,
                str_contains($cStr, 's2') || str_contains($cStr, 'magister') => 6,
                str_contains($cStr, 's1') || str_contains($cStr, 'sarjana') || str_contains($cStr, 'd4') || str_contains($cStr, 'profesi') => 5,
                str_contains($cStr, 'd3') || str_contains($cStr, 'd2') || str_contains($cStr, 'd1') || str_contains($cStr, 'diploma') => 4,
                str_contains($cStr, 'sma') || str_contains($cStr, 'smk') || str_contains($cStr, 'sederajat') => 3,
                str_contains($cStr, 'smp') => 2,
                str_contains($cStr, 'sd') => 1,
                default => null,
            };
        }

        $jLevel = is_object($jobEdu) ? ($jobEdu->sort_order ?? $jobEdu->id ?? null) : (is_numeric($jobEdu) ? (int)$jobEdu : null);
        $jName = is_object($jobEdu) ? ($jobEdu->name ?? 'Semua Jenjang') : (is_string($jobEdu) && $jobEdu !== '' ? $jobEdu : 'Semua Jenjang');

        if (!$jLevel) {
            return [
                'is_matched' => true,
                'status' => 'semua_jenjang',
                'label' => 'Terbuka Semua Jenjang',
                'score' => 100,
                'required_level' => 'Semua Jenjang',
                'candidate_level' => $cName !== '-' ? $cName : 'Belum Ditentukan',
            ];
        }

        if (!$cLevel) {
            return [
                'is_matched' => true,
                'status' => 'belum_diisi',
                'label' => 'Pendidikan Belum Terdata',
                'score' => 60,
                'required_level' => $jName,
                'candidate_level' => 'Belum Ditentukan',
            ];
        }

        if ($cLevel >= $jLevel) {
            return [
                'is_matched' => true,
                'status' => 'sesuai',
                'label' => 'Memenuhi Syarat (' . $cName . ')',
                'score' => 100,
                'required_level' => $jName,
                'candidate_level' => $cName,
            ];
        }

        $gap = $jLevel - $cLevel;
        return [
            'is_matched' => false,
            'status' => 'di_bawah_syarat',
            'label' => 'Di Bawah Syarat (Butuh Min. ' . $jName . ')',
            'score' => 0,
            'gap_levels' => $gap,
            'required_level' => $jName,
            'candidate_level' => $cName,
        ];
    }

    /**
     * Mengevaluasi kesesuaian KBJI (Klasifikasi Baku Jabatan Indonesia)
     * Sekarang bersifat SOFT COMPATIBILITY (Bukan Hard Gatekeeper)
     */
    public function evaluateKbjiMatch(?object $candidateKbji, ?object $jobKbji): array
    {
        if (!$candidateKbji || !$jobKbji) {
            return [
                'is_compatible' => true,
                'match_level' => 'unspecified',
                'label' => 'KBJI Fleksibel',
                'code' => null,
                'compatibility_score' => 60,
            ];
        }

        $cCode = trim($candidateKbji->code);
        $jCode = trim($jobKbji->code);

        // Level 1: Jabatan Identik (exact) - Kode KBJI sama persis
        if ($cCode === $jCode) {
            return [
                'is_compatible' => true,
                'match_level' => 'exact',
                'label' => 'Jabatan Identik',
                'code' => $cCode,
                'compatibility_score' => 100,
            ];
        }

        // Level 2: Sub-Golongan Identik (unit_group) - 4 digit pertama sama
        $cUnit = substr($cCode, 0, 4);
        $jUnit = substr($jCode, 0, 4);
        if (strlen($cUnit) === 4 && strlen($jUnit) === 4 && $cUnit === $jUnit) {
            return [
                'is_compatible' => true,
                'match_level' => 'unit_group',
                'label' => 'Sub-Golongan Identik (' . $cUnit . ')',
                'code' => $cUnit,
                'compatibility_score' => 85,
            ];
        }

        // Level 3: Golongan Pokok Identik - 2 digit pertama sama
        $cMajor = substr($cCode, 0, 2);
        $jMajor = substr($jCode, 0, 2);
        if (strlen($cMajor) === 2 && strlen($jMajor) === 2 && $cMajor === $jMajor) {
            return [
                'is_compatible' => true,
                'match_level' => 'sub_major',
                'label' => 'Golongan Pokok Serumpun (' . $cMajor . ')',
                'code' => $cMajor,
                'compatibility_score' => 60,
            ];
        }

        // Berbeda sub-golongan: tetap kompatibel secara lunak, jangan dibuang
        return [
            'is_compatible' => true,
            'match_level' => 'different_group',
            'label' => 'Lintas Bidang Jabatan',
            'code' => $cCode . ' vs ' . $jCode,
            'compatibility_score' => 30,
        ];
    }

    /**
     * Standar Aturan Keputusan (Decision Rules Sesuai Rencana Awal)
     * 1. Perfect Match (100%)
     * 2. High Match (70% - 99%)
     * 3. Gap Match (40% - 69%)
     * 4. Low Match (< 40%)
     */
    public function classifyScore(int $score): array
    {
        if ($score >= 100) {
            return [
                'category' => 'perfect',
                'label' => '100% Siap Kerja (Perfect Match)',
                'badge_color' => 'emerald',
                'action_label' => 'Rekomendasikan ke Perusahaan',
                'action_key' => 'recommend_company',
                'description' => 'Seluruh kualifikasi dan kompetensi lowongan telah dipenuhi pelamar.',
            ];
        } elseif ($score >= 70) {
            return [
                'category' => 'high',
                'label' => 'Siap Ditempatkan Langsung (High Match)',
                'badge_color' => 'blue',
                'action_label' => 'Rekomendasikan ke Perusahaan',
                'action_key' => 'recommend_company',
                'description' => 'Memenuhi sebagian besar kompetensi dan kualifikasi yang disyaratkan.',
            ];
        } elseif ($score >= 40) {
            return [
                'category' => 'gap',
                'label' => 'Perlu Pelatihan Skill Gap (Gap Match)',
                'badge_color' => 'amber',
                'action_label' => 'Lihat Detail Gap',
                'action_key' => 'skill_gap_details',
                'description' => 'Memiliki modal dasar, namun terdapat kesenjangan skill yang perlu ditingkatkan.',
            ];
        } else {
            return [
                'category' => 'low',
                'label' => 'Belum Sesuai (Low Match)',
                'badge_color' => 'slate',
                'action_label' => 'Bimbingan Karir Dasar',
                'action_key' => 'career_guidance',
                'description' => 'Kesesuaian kualifikasi masih rendah untuk formasi lowongan kerja ini.',
            ];
        }
    }

    /**
     * Menghitung nilai kemiripan teks (0.0 - 1.0) menggunakan tokenisasi & Jaccard/Dice overlap
     */
    public function calculateTextSimilarity(string $textA, string $textB): float
    {
        $tokensA = $this->tokenizeText($textA);
        $tokensB = $this->tokenizeText($textB);

        if (empty($tokensA) || empty($tokensB)) {
            return 0.0;
        }

        // Substring direct check
        $cleanA = implode(' ', $tokensA);
        $cleanB = implode(' ', $tokensB);
        if (str_contains($cleanA, $cleanB) || str_contains($cleanB, $cleanA)) {
            return 0.85;
        }

        // Token intersection
        $intersection = array_intersect($tokensA, $tokensB);
        $union = array_unique(array_merge($tokensA, $tokensB));

        if (empty($union)) {
            return 0.0;
        }

        // Jaccard similarity
        $jaccard = count($intersection) / count($union);

        // Dice coefficient (lebih memberi reward pada irisan yang cocok)
        $dice = (2 * count($intersection)) / (count($tokensA) + count($tokensB));

        return max($jaccard, $dice);
    }

    /**
     * Tokenisasi teks dan eliminasi stopwords
     */
    public function tokenizeText(string $text): array
    {
        $clean = mb_strtolower(trim($text));
        $clean = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $clean);
        $words = preg_split('/\s+/', $clean, -1, PREG_SPLIT_NO_EMPTY);

        $filtered = [];
        foreach ($words as $word) {
            if (mb_strlen($word) >= 2 && !in_array($word, $this->stopWords)) {
                $filtered[] = $word;
            }
        }

        return array_values(array_unique($filtered));
    }

    /**
     * Ekstraksi item keahlian dari teks bebas (pemisah koma, titik dua, garis baru)
     */
    public function extractSkillsFromText(string $text): array
    {
        if (empty(trim($text))) {
            return [];
        }

        $parts = preg_split('/[,;\n\r]+/', $text);
        $skills = [];

        foreach ($parts as $part) {
            $cleaned = trim(preg_replace('/[^\p{L}\p{N}\s\-]/u', '', $part));
            if (mb_strlen($cleaned) >= 2 && !in_array(mb_strtolower($cleaned), $this->stopWords)) {
                $skills[] = ucwords(mb_strtolower($cleaned));
            }
        }

        return array_values(array_unique($skills));
    }

    /**
     * Lemmatisasi & Normalisasi Semantik Dwibahasa (ID <-> EN)
     * Menjembatani ribuan padanan istilah teknis/profesional lintas bahasa secara sistemik tanpa false positive
     */
    public function canonicalizeBilingualConcept(string $text): array
    {
        $tokens = $this->tokenizeText($text);
        if (empty($tokens)) return [];

        static $dict = [
            // Tech, IT & Digital
            'technology' => 'teknologi', 'technologies' => 'teknologi', 'teknologi' => 'teknologi',
            'technical' => 'teknis', 'technics' => 'teknis', 'teknis' => 'teknis',
            'trend' => 'tren', 'trends' => 'tren', 'tren' => 'tren',
            'system' => 'sistem', 'systems' => 'sistem', 'sistem' => 'sistem',
            'network' => 'jaringan', 'networking' => 'jaringan', 'jaringan' => 'jaringan',
            'development' => 'kembang', 'developer' => 'developer', 'developing' => 'kembang', 'pengembangan' => 'kembang',
            'programming' => 'program', 'programmer' => 'program', 'pemrograman' => 'program',
            'software' => 'software', 'hardware' => 'hardware', 'database' => 'database',
            'application' => 'aplikasi', 'applications' => 'aplikasi', 'aplikasi' => 'aplikasi',
            'security' => 'keamanan', 'keamanan' => 'keamanan', 'cyber' => 'siber', 'siber' => 'siber',
            'solution' => 'solusi', 'solutions' => 'solusi', 'solusi' => 'solusi',
            'api' => 'api', 'apis' => 'api', 'rest' => 'api', 'restful' => 'api',
            'microservice' => 'arsitektur', 'microservices' => 'arsitektur',
            'frontend' => 'frontend', 'backend' => 'backend', 'fullstack' => 'fullstack',
            'data' => 'data', 'intelligence' => 'intelijen', 'bi' => 'intelijen',
            'sql' => 'sql', 'cloud' => 'cloud', 'devops' => 'devops',
            'framework' => 'framework', 'query' => 'query', 'tuning' => 'optimasi',

            // Business & Management
            'business' => 'bisnis', 'bisnis' => 'bisnis',
            'management' => 'manajemen', 'managing' => 'manajemen', 'manajemen' => 'manajemen',
            'managerial' => 'manajerial', 'manajerial' => 'manajerial',
            'project' => 'proyek', 'projects' => 'proyek', 'proyek' => 'proyek',
            'operation' => 'operasi', 'operations' => 'operasi', 'operational' => 'operasi', 'operasional' => 'operasi',
            'administration' => 'administrasi', 'administrative' => 'administrasi', 'administrasi' => 'administrasi',
            'strategy' => 'strategi', 'strategic' => 'strategi', 'strategis' => 'strategi', 'strategi' => 'strategi',
            'planning' => 'rencana', 'plan' => 'rencana', 'perencanaan' => 'rencana',
            'analysis' => 'analisis', 'analytics' => 'analisis', 'analyst' => 'analisis', 'analisis' => 'analisis', 'analis' => 'analisis',
            'coordination' => 'koordinasi', 'coordinator' => 'koordinasi', 'koordinasi' => 'koordinasi',
            'supervision' => 'awasi', 'supervisor' => 'supervisor', 'pengawasan' => 'awasi',
            'leadership' => 'pimpin', 'kepemimpinan' => 'pimpin',
            'communication' => 'komunikasi', 'communicative' => 'komunikasi', 'communicate' => 'komunikasi', 'communicating' => 'komunikasi', 'komunikasi' => 'komunikasi',
            'negotiation' => 'negosiasi', 'negotiating' => 'negosiasi', 'negotiate' => 'negosiasi', 'negosiasi' => 'negosiasi',
            'presentation' => 'presentasi', 'presenting' => 'presentasi', 'present' => 'presentasi', 'presentasi' => 'presentasi',
            'coordination' => 'koordinasi', 'coordinating' => 'koordinasi', 'coordinate' => 'koordinasi', 'koordinasi' => 'koordinasi',
            'collaboration' => 'kolaborasi', 'collaborating' => 'kolaborasi', 'collaborate' => 'kolaborasi', 'teamwork' => 'tim', 'kerjasama' => 'tim',
            'document' => 'dokumen', 'documentation' => 'dokumen', 'documents' => 'dokumen', 'dokumen' => 'dokumen',
            'filing' => 'arsip', 'archive' => 'arsip', 'pengarsipan' => 'arsip', 'arsip' => 'arsip',

            // Sales, Marketing & Customer Relations
            'sales' => 'jual', 'selling' => 'jual', 'penjualan' => 'jual',
            'marketing' => 'pasar', 'pemasaran' => 'pasar',
            'customer' => 'pelanggan', 'client' => 'pelanggan', 'clients' => 'pelanggan', 'pelanggan' => 'pelanggan', 'konsumen' => 'pelanggan', 'klien' => 'pelanggan',
            'relationship' => 'relasi', 'relation' => 'relasi', 'relations' => 'relasi', 'hubungan' => 'relasi', 'relasi' => 'relasi',
            'service' => 'layani', 'services' => 'layani', 'pelayanan' => 'layani', 'layanan' => 'layani',
            'support' => 'dukung', 'dukungan' => 'dukung',
            'b2b' => 'bisnis', 'b2c' => 'pelanggan', 'crm' => 'pelanggan',
            'acquisition' => 'akuisisi', 'akuisisi' => 'akuisisi',
            'retention' => 'retensi', 'retensi' => 'retensi',
            'lead' => 'prospek', 'leads' => 'prospek', 'prospek' => 'prospek',
            'account' => 'akun', 'brand' => 'merek', 'branding' => 'merek', 'merek' => 'merek',
            'campaign' => 'kampanye', 'campaigns' => 'kampanye', 'kampanye' => 'kampanye',
            'growth' => 'kembang',

            // Finance, Accounting & Tax
            'finance' => 'keuangan', 'financial' => 'keuangan', 'keuangan' => 'keuangan',
            'accounting' => 'akuntansi', 'accountant' => 'akuntansi', 'akuntansi' => 'akuntansi', 'pembukuan' => 'akuntansi',
            'budget' => 'anggaran', 'budgeting' => 'anggaran', 'anggaran' => 'anggaran',
            'tax' => 'pajak', 'taxation' => 'pajak', 'pajak' => 'pajak', 'pph' => 'pajak', 'ppn' => 'pajak', 'vat' => 'pajak',
            'audit' => 'audit', 'auditing' => 'audit',
            'forecast' => 'rencana', 'forecasting' => 'rencana', 'proyeksi' => 'rencana',
            'payable' => 'utang', 'receivable' => 'piutang',
            'reconciliation' => 'rekonsiliasi', 'rekonsiliasi' => 'rekonsiliasi',
            'payroll' => 'gaji', 'gaji' => 'gaji',

            // Logistics, Warehouse & Procurement
            'warehouse' => 'gudang', 'warehousing' => 'gudang', 'gudang' => 'gudang', 'pergudangan' => 'gudang',
            'inventory' => 'inventaris', 'inventaris' => 'inventaris', 'stok' => 'inventaris', 'stock' => 'inventaris',
            'logistics' => 'logistik', 'logistik' => 'logistik',
            'distribution' => 'distribusi', 'distribusi' => 'distribusi',
            'procurement' => 'pengadaan', 'pengadaan' => 'pengadaan',
            'purchasing' => 'pengadaan', 'pembelian' => 'pengadaan', 'sourcing' => 'pengadaan', 'beli' => 'pengadaan',
            'supply' => 'rantai_pasok', 'chain' => 'rantai_pasok',
            'customs' => 'pabean', 'kepabeanan' => 'pabean',
            'import' => 'impor', 'export' => 'ekspor', 'impor' => 'impor', 'ekspor' => 'ekspor',

            // Engineering, Machine & Quality
            'engineering' => 'teknik', 'engineer' => 'teknik', 'teknik' => 'teknik',
            'machine' => 'mesin', 'machinery' => 'mesin', 'mesin' => 'mesin',
            'maintenance' => 'rawat', 'pemeliharaan' => 'rawat', 'perawatan' => 'rawat',
            'repair' => 'perbaiki', 'perbaikan' => 'perbaiki',
            'installation' => 'pasang', 'instalasi' => 'pasang',
            'quality' => 'mutu', 'kualitas' => 'mutu', 'mutu' => 'mutu',
            'assurance' => 'jamin', 'penjaminan' => 'jamin',
            'control' => 'kontrol', 'pengendalian' => 'kontrol', 'kontrol' => 'kontrol',
            'troubleshooting' => 'solusi', 'troubleshoot' => 'solusi',
            'preventive' => 'cegah', 'pencegahan' => 'cegah',

            // Office & Administration
            'office' => 'kantor', 'kantor' => 'kantor',
            'facility' => 'fasilitas', 'facilities' => 'fasilitas', 'fasilitas' => 'fasilitas',

            // Stakeholders, Vendors, Optimization, Reports, Safety
            'stakeholder' => 'pemangku', 'stakeholders' => 'pemangku', 'pemangku' => 'pemangku',
            'vendor' => 'vendor', 'vendors' => 'vendor', 'supplier' => 'vendor', 'suppliers' => 'vendor', 'pemasok' => 'vendor',
            'process' => 'proses', 'processes' => 'proses', 'proses' => 'proses',
            'optimize' => 'optimasi', 'optimizing' => 'optimasi', 'optimization' => 'optimasi', 'optimasi' => 'optimasi',
            'report' => 'laporan', 'reports' => 'laporan', 'reporting' => 'laporan', 'laporan' => 'laporan',
            'schedule' => 'jadwal', 'schedules' => 'jadwal', 'penjadwalan' => 'jadwal', 'jadwal' => 'jadwal',
            'protocol' => 'protokol', 'protocols' => 'protokol', 'protokol' => 'protokol',
            'safety' => 'k3', 'k3' => 'k3', 'hse' => 'k3',
            'contract' => 'kontrak', 'contracts' => 'kontrak', 'kontrak' => 'kontrak', 'agreement' => 'kontrak', 'perjanjian' => 'kontrak',
            'drafting' => 'susun',
            'inquiry' => 'layani', 'inquiries' => 'layani',
            'complaint' => 'keluhan', 'complaints' => 'keluhan', 'keluhan' => 'keluhan',
            'recovery' => 'pulih',
            'reception' => 'resepsionis', 'receptionist' => 'resepsionis', 'resepsionis' => 'resepsionis',
            'hospitality' => 'ramah',

            // HR, Legal & Medical
            'human' => 'sdm', 'resources' => 'sdm', 'personalia' => 'sdm', 'hrd' => 'sdm',
            'recruitment' => 'rekrut', 'rekrutmen' => 'rekrut', 'recruiting' => 'rekrut',
            'talent' => 'bakat', 'talenta' => 'bakat',
            'employee' => 'karyawan', 'karyawan' => 'karyawan', 'pegawai' => 'karyawan',
            'industrial' => 'industrial',
            'legal' => 'hukum', 'hukum' => 'hukum', 'compliance' => 'patuh', 'kepatuhan' => 'patuh', 'governance' => 'kelola',
            'medical' => 'medis', 'medis' => 'medis', 'nurse' => 'rawat', 'perawat' => 'rawat', 'keperawatan' => 'rawat',
        ];

        static $modifiersToIgnore = [
            'monthly', 'annual', 'daily', 'weekly', 'cross', 'functional', 'global', 'local',
            'routine', 'accuracy', 'accurate', 'internal', 'external', 'major', 'minor',
            'critical', 'prompt', 'diligently', 'consistently', 'effectively', 'accurately',
            'strictly', 'closely', 'regularly', 'actively', 'various', 'diverse',
            'seamlessly', 'properly', 'continuously', 'frequently', 'periodically',
            'efficiently', 'professionally', 'proactively', 'successfully', 'independently',
            'directly', 'secara', 'berkala', 'rutin', 'intensif', 'efektif', 'efisien',
            'konsisten', 'mandiri', 'profesional', 'akurat', 'langsung'
        ];

        $canonical = [];
        foreach ($tokens as $t) {
            if (in_array($t, $modifiersToIgnore)) {
                continue;
            }

            // 1. Cek kamus bentuk asli terlebih dahulu (mencegah kata berakhiran 'ss' seperti 'business' terpangkas keliru)
            if (isset($dict[$t])) {
                $canonical[] = $dict[$t];
                continue;
            }

            // 2. Normalisasi bentuk jamak / infleksi Bahasa Inggris jika belum ada di kamus
            $lemma = $t;
            if (str_ends_with($t, 'ies') && mb_strlen($t) > 4) {
                $lemma = mb_substr($t, 0, -3) . 'y';
            } elseif (str_ends_with($t, 'sses') && mb_strlen($t) > 5) {
                $lemma = mb_substr($t, 0, -2);
            } elseif (str_ends_with($t, 'es') && mb_strlen($t) > 4 && !in_array($t, ['proses', 'akses'])) {
                $lemma = mb_substr($t, 0, -2);
            } elseif (str_ends_with($t, 's') && mb_strlen($t) > 3 && !str_ends_with($t, 'ss') && !in_array($t, ['analisis', 'bisnis', 'kasir', 'pos', 'ops'])) {
                $lemma = mb_substr($t, 0, -1);
            }

            if (isset($dict[$lemma])) {
                $canonical[] = $dict[$lemma];
            } elseif (mb_strlen($lemma) >= 4 && !in_array($lemma, $this->stopWords) && !in_array($lemma, $modifiersToIgnore)) {
                $canonical[] = $lemma;
            }
        }

        return array_values(array_unique($canonical));
    }
}
