<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class JobSeeker extends Model
{
    use HasFactory;

    protected $table = 'req_pk_pencaker';
    public $timestamps = false; // Asumsi tidak ada created_at/updated_at di req_pk_pencaker

    // Konstanta tetap dipertahankan agar tidak error di bagian lain
    public const STUDY_FIELD_GROUPS = [
        'Umum (SD / SMP / SMA)',
        'Pendidikan',
        'Humaniora & Seni',
        'Ilmu Sosial, Bisnis, & Hukum',
        'Sains, Matematika, & Statistika',
        'Teknologi Informasi & Komunikasi (TIK)',
        'Teknik, Manufaktur, & Konstruksi',
        'Pertanian, Kehutanan, Perikanan, & Peternakan',
        'Kesehatan & Kesejahteraan Sosial',
        'Pariwisata, Perhotelan, & Jasa',
        'Lainnya',
    ];

    public const EXPERIENCE_RANGES = [
        'fresh_graduate',
        '<1',
        '1-3',
        '3-5',
        '>5',
    ];

    protected $fillable = [
        'profile_id',
        'name',
        'provinsi',
        'province_id',
        'kab_kota',
        'regency_id',
        'region_name',
        'umur',
        'jenis_kelamin',
        'kondisi_fisik',
        'jenis_disabilitas',
        'marital',
        'status_bekerja',
        'start_date',
        'recent_start',
        'status_sekarang',
        'tanggal_kedaluwarsa',
        'pendidikan',
        'education_level_id',
        'nama_sekolah',
        'jurusan',
        'experience',
        'sertifikasi',
        'lembaga_pelatihan',
        'progpel',
        'keahlian',
        'bahasa',
        'rencana_kerja_luar_negeri',
        'country_wish',
        'lamaran_diajukan',
    ];

    public function province()
    {
        return $this->belongsTo(Province::class, 'province_id');
    }

    public function regency()
    {
        return $this->belongsTo(Regency::class, 'regency_id');
    }

    public function educationLevel()
    {
        return $this->belongsTo(EducationLevel::class, 'education_level_id');
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(
            SkillNode::class,
            'pencaker_esco_skills',
            'pencaker_id',
            'esco_skill_id'
        )->withPivot('is_manual')->withTimestamps();
    }
}
