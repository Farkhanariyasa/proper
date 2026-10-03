<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class JobSeeker extends Model
{
    use HasFactory;

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
        'nik',
        'full_name',
        'phone',
        'birth_date',
        'gender',
        'regency_id',
        'education_level_id',
        'study_field_group',
        'study_field_detail',
        'experience_range',
        'desired_occupation',
        'kbji_id',
        'trainings',
        'certifications',
        'created_by',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'trainings' => 'array',
        'certifications' => 'array',
    ];

    public function kbji(): BelongsTo
    {
        return $this->belongsTo(KbjiClassification::class, 'kbji_id');
    }

    public function regency(): BelongsTo
    {
        return $this->belongsTo(Regency::class, 'regency_id', 'id');
    }

    public function educationLevel(): BelongsTo
    {
        return $this->belongsTo(EducationLevel::class, 'education_level_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(
            SkillNode::class,
            'job_seeker_skills',
            'job_seeker_id',
            'esco_skill_id'
        );
    }
}
