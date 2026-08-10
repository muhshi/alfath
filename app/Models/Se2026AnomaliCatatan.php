<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Se2026AnomaliCatatan extends Model
{
    protected $connection = 'fasih';
    protected $table = 'se2026_anomali_catatan';

    public $timestamps = true;

    protected $guarded = [];

    protected $casts = [
        'approved_at' => 'datetime',
    ];
}
