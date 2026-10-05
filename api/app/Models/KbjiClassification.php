<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KbjiClassification extends Model
{
    use HasFactory;

    protected $table = 'kbji_classifications_2026';

    public $timestamps = false;

    protected $fillable = [
        'code',
        'title',
        'level',
        'parent_code',
        'description',
    ];

    public function lowongan(): HasMany
    {
        return $this->hasMany(LowonganKerja::class, 'kbji_id');
    }
}
