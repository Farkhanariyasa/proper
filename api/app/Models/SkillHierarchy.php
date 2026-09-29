<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkillHierarchy extends Model
{
    use HasFactory;

    protected $table = 'skill_hierarchy';
    public $timestamps = false;

    protected $fillable = [
        'parent_id',
        'child_id',
        'sort_order',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(SkillNode::class, 'parent_id');
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(SkillNode::class, 'child_id');
    }
}
