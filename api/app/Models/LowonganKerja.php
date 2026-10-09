<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class LowonganKerja extends Model
{
    use HasFactory;

    /**
     * Tabel asli data lowongan kerja adalah req_pk_loker
     */
    protected $table = 'req_pk_loker';

    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $appends = [
        'judul_lowongan',
        'status_lowongan',
        'jumlah_kebutuhan',
        'slug',
        'sistem_kerja',
        'gaji_tampilkan',
        'gaji_minimal',
        'gaji_maksimal',
        'pengalaman_minimal_tahun',
        'jurusan_studi',
        'tanggal_buka',
        'tanggal_tutup',
        'kbji_id',
    ];

    protected $fillable = [
        'job_id',
        'vac_id',
        'kode_wlkp',
        'nama_perusahaan',
        'region_pembeker',
        'judul_pekerjaan',
        'deskripsi_pekerjaan',
        'status_loker',
        'tipe_pekerjaan',
        'bidang_pekerjaan',
        'industri',
        'kuota',
        'reg',
        'kontak_loker',
        'rentang_gaji',
        'tanggal_tayang',
        'tanggal_expired_lowongan',
        'tanggal_dibuat',
        'tanggal_update',
        'jumlah_pelamar',
        'lamaran_diterima',
        'lamaran_ditolak',
        'jumlah_diwawancara',
        'lamaran_dibatalkan',
        'kbji_2026_id',
        'is_mapped',
        'provinsi_id',
        'regency_id',
        'education_level_id',
        // Alias kompatibilitas
        'judul_lowongan',
        'status_lowongan',
        'jumlah_kebutuhan',
        'created_by',
    ];

    protected $casts = [
        'kuota' => 'integer',
        'tanggal_tayang' => 'datetime',
        'tanggal_expired_lowongan' => 'datetime',
        'tanggal_dibuat' => 'datetime',
        'tanggal_update' => 'datetime',
        'jumlah_pelamar' => 'integer',
        'lamaran_diterima' => 'integer',
        'lamaran_ditolak' => 'integer',
        'jumlah_diwawancara' => 'integer',
        'lamaran_dibatalkan' => 'integer',
        'is_mapped' => 'boolean',
    ];

    public function kbji(): BelongsTo
    {
        return $this->belongsTo(KbjiClassification2026::class, 'kbji_2026_id');
    }

    public function educationLevel(): BelongsTo
    {
        return $this->belongsTo(EducationLevel::class, 'education_level_id');
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'provinsi_id', 'id');
    }

    public function regency(): BelongsTo
    {
        return $this->belongsTo(Regency::class, 'regency_id', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(
            SkillNode::class,
            'lowongan_skills',
            'vac_id',
            'esco_skill_id',
            'vac_id',
            'id'
        )->withPivot(['tipe_keahlian', 'skor', 'metode']);
    }

    public function scopePublished($query)
    {
        return $query->whereIn('status_loker', ['published', 'tayang']);
    }

    public function scopeFilter($query, array $filters)
    {
        if (!empty($filters['search'])) {
            $term = '%' . trim($filters['search']) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('judul_pekerjaan', 'ILIKE', $term)
                  ->orWhere('nama_perusahaan', 'ILIKE', $term);
            });
        }

        if (!empty($filters['tipe_pekerjaan'])) {
            $query->where('tipe_pekerjaan', 'ILIKE', '%' . $filters['tipe_pekerjaan'] . '%');
        }

        if (!empty($filters['status_lowongan'])) {
            $status = strtolower($filters['status_lowongan']);
            if ($status === 'published') {
                $query->whereIn('status_loker', ['published', 'tayang']);
            } else {
                $query->where('status_loker', $status);
            }
        }

        if (!empty($filters['provinsi_id'])) {
            $query->where('provinsi_id', $filters['provinsi_id']);
        }

        if (!empty($filters['regency_id'])) {
            $query->where('regency_id', $filters['regency_id']);
        }

        if (!empty($filters['education_level_id'])) {
            $query->where('education_level_id', $filters['education_level_id']);
        }

        if (!empty($filters['kbji_id'])) {
            $query->where('kbji_2026_id', $filters['kbji_id']);
        }

        return $query;
    }

    // Accessors & Mutators untuk kompatibilitas
    public function getJudulLowonganAttribute()
    {
        return $this->judul_pekerjaan;
    }

    public function setJudulLowonganAttribute($value)
    {
        $this->attributes['judul_pekerjaan'] = $value;
    }

    public function getStatusLowonganAttribute()
    {
        return $this->status_loker;
    }

    public function setStatusLowonganAttribute($value)
    {
        $this->attributes['status_loker'] = $value;
    }

    public function getJumlahKebutuhanAttribute()
    {
        return $this->kuota;
    }

    public function getKbjiIdAttribute()
    {
        return $this->kbji_2026_id;
    }

    public function getTanggalBukaAttribute()
    {
        return $this->tanggal_tayang;
    }

    public function getTanggalTutupAttribute()
    {
        return $this->tanggal_expired_lowongan;
    }

    public function getSlugAttribute()
    {
        return $this->job_id ?: (string)$this->id;
    }

    public function getSistemKerjaAttribute()
    {
        return 'Onsite';
    }

    public function getPengalamanMinimalTahunAttribute()
    {
        return 0;
    }

    public function getJurusanStudiAttribute()
    {
        return null;
    }

    public function getGajiTampilkanAttribute()
    {
        return !empty($this->rentang_gaji);
    }

    public function getGajiMinimalAttribute()
    {
        if (!$this->rentang_gaji) return null;
        $parts = explode('-', $this->rentang_gaji);
        return isset($parts[0]) && is_numeric(trim($parts[0])) ? (float)trim($parts[0]) : null;
    }

    public function getGajiMaksimalAttribute()
    {
        if (!$this->rentang_gaji) return null;
        $parts = explode('-', $this->rentang_gaji);
        return isset($parts[1]) && is_numeric(trim($parts[1])) ? (float)trim($parts[1]) : null;
    }
}
