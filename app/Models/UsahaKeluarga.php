<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsahaKeluarga extends Model
{
    protected $connection = 'fasih';
    protected $table = 'se2026_usaha_keluarga';

    public $timestamps = true;

    protected $guarded = [];
}
