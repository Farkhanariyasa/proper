<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SkillNode extends Model
{
    use HasFactory;

    protected $table = 'skill_nodes';

    protected $fillable = [
        'code',
        'type',
        'is_layer1',
        'title',
        'description',
        'alt_labels',
        'title_en',
        'description_en',
        'alt_labels_en',
        'source_uri',
        'metadata',
    ];

    /**
     * Sembunyikan source_uri secara absolut dari serialisasi model
     */
    protected $hidden = [
        'source_uri',
    ];

    protected $casts = [
        'is_layer1' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Parser string PostgreSQL text[] ke PHP array murni
     */
    public static function parsePgArray($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (empty($value) || $value === '{}' || !is_string($value)) {
            return [];
        }

        $trimmed = trim($value, '{}');
        if ($trimmed === '') {
            return [];
        }

        // Parse format CSV PostgreSQL text[]
        $result = str_getcsv($trimmed);
        return array_map(function ($item) {
            return trim($item, '"');
        }, $result);
    }

    public function getAltLabelsAttribute($value): array
    {
        return self::parsePgArray($value);
    }

    public function getAltLabelsEnAttribute($value): array
    {
        return self::parsePgArray($value);
    }

    /**
     * Relasi ke node anak (children)
     */
    public function children(): BelongsToMany
    {
        return $this->belongsToMany(
            SkillNode::class,
            'skill_hierarchy',
            'parent_id',
            'child_id'
        )->withPivot('sort_order')->orderByPivot('sort_order');
    }

    /**
     * Relasi ke node induk (parents / broader)
     */
    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(
            SkillNode::class,
            'skill_hierarchy',
            'child_id',
            'parent_id'
        )->withPivot('sort_order');
    }
    /**
     * Relasi ke pencari kerja yang memiliki keahlian ini
     */
    public function jobSeekers(): BelongsToMany
    {
        return $this->belongsToMany(
            JobSeeker::class,
            'job_seeker_skills',
            'esco_skill_id',
            'job_seeker_id'
        );
    }
}
