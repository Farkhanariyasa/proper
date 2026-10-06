<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KbjiAlias extends Model
{
    protected $table = 'kbji_aliases';
    protected $guarded = ['id'];

    public function kbji()
    {
        return $this->belongsTo(KbjiClassification2026::class, 'kbji_id');
    }
}
