<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Se2026PemutakhiranKeluarga extends Model
{
    protected $connection = 'fasih';
    protected $table = 'se2026_pemutakhiran_keluarga';

    public $timestamps = true;

    protected $guarded = [];
}
