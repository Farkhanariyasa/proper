<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PencakerEscoSkill extends Model
{
    use HasFactory;

    protected $table = 'pencaker_esco_skills';

    protected $fillable = [
        'pencaker_id',
        'esco_skill_id',
        'is_manual'
    ];

    public function pencaker()
    {
        return $this->belongsTo(JobSeeker::class, 'pencaker_id', 'id');
    }

    public function escoSkill()
    {
        return $this->belongsTo(SkillNode::class, 'esco_skill_id', 'id');
    }
}
