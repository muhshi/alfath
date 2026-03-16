<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GcPln extends Model
{
    protected $connection = 'fasih';
    protected $table = 'GC_PLN';
    
    public $timestamps = false;

    protected $guarded = [];
}
