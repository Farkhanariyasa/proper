<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EducationLevel extends Model
{
    protected $fillable = [
        'name',
        'sort_order',
    ];

    public function jobSeekers(): HasMany
    {
        return $this->hasMany(JobSeeker::class, 'education_level_id');
    }
}
