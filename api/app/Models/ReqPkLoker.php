<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReqPkLoker extends Model
{
    use HasFactory;

    protected $table = 'req_pk_loker';
    public $timestamps = false; // We use custom timestamp columns

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

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'provinsi_id');
    }

    public function regency(): BelongsTo
    {
        return $this->belongsTo(Regency::class, 'regency_id');
    }

    public function skills()
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
            // Mapping status dari frontend ke status_loker
            // Frontend: 'Published', 'Closed', 'Archived', 'Draft'
            // DB: 'tayang', 'expired', 'closed', dll
            $status = strtolower($filters['status_lowongan']);
            if ($status === 'published') $status = 'tayang';
            $query->where('status_loker', $status);
        }

        if (!empty($filters['kbji_id'])) {
            $query->where('kbji_2026_id', $filters['kbji_id']);
        }

        return $query;
    }
}
