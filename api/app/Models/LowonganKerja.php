<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LowonganKerja extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'lowongan_kerja';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'slug',
        'nama_perusahaan',
        'judul_lowongan',
        'kbji_id',
        'lapangan_usaha_id',
        'deskripsi_pekerjaan',
        'tipe_pekerjaan',
        'sistem_kerja',
        'jumlah_kebutuhan',
        'education_level_id',
        'jurusan_studi',
        'pengalaman_minimal_tahun',
        'usia_minimal',
        'usia_maksimal',
        'jenis_kelamin',
        'is_disabilitas',
        'persyaratan_tambahan',
        'provinsi_id',
        'regency_id',
        'alamat_lengkap_penempatan',
        'gaji_tampilkan',
        'gaji_minimal',
        'gaji_maksimal',
        'status_lowongan',
        'tanggal_buka',
        'tanggal_tutup',
        'created_by',
    ];

    protected $casts = [
        'is_disabilitas' => 'boolean',
        'gaji_tampilkan' => 'boolean',
        'gaji_minimal' => 'float',
        'gaji_maksimal' => 'float',
        'tanggal_buka' => 'datetime',
        'tanggal_tutup' => 'datetime',
        'pengalaman_minimal_tahun' => 'integer',
        'jumlah_kebutuhan' => 'integer',
        'usia_minimal' => 'integer',
        'usia_maksimal' => 'integer',
    ];

    public function kbji(): BelongsTo
    {
        return $this->belongsTo(KbjiClassification::class, 'kbji_id');
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
            'id',
            'id'
        )->withPivot(['tipe_keahlian', 'skor', 'metode']);
    }

    public function scopePublished($query)
    {
        return $query->where('status_lowongan', 'Published')
            ->where('tanggal_tutup', '>=', now());
    }

    public function scopeFilter($query, array $filters)
    {
        if (!empty($filters['search'])) {
            $term = '%' . trim($filters['search']) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('judul_lowongan', 'ILIKE', $term)
                  ->orWhere('nama_perusahaan', 'ILIKE', $term);
            });
        }

        if (!empty($filters['provinsi_id'])) {
            $query->where('provinsi_id', $filters['provinsi_id']);
        }

        if (!empty($filters['regency_id'])) {
            $query->where('regency_id', $filters['regency_id']);
        }

        if (!empty($filters['tipe_pekerjaan'])) {
            $query->where('tipe_pekerjaan', $filters['tipe_pekerjaan']);
        }

        if (!empty($filters['sistem_kerja'])) {
            $query->where('sistem_kerja', $filters['sistem_kerja']);
        }

        if (!empty($filters['status_lowongan'])) {
            $query->where('status_lowongan', $filters['status_lowongan']);
        }

        if (!empty($filters['education_level_id'])) {
            $query->where('education_level_id', $filters['education_level_id']);
        }

        if (!empty($filters['kbji_id'])) {
            $query->where('kbji_id', $filters['kbji_id']);
        }

        return $query;
    }
}
