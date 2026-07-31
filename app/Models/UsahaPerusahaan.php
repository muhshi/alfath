<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsahaPerusahaan extends Model
{
    protected $connection = 'fasih';
    protected $table = 'usaha_perusahaan';

    public $timestamps = true;

    protected $guarded = [];
}
