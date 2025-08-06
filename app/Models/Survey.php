<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Survey extends Model
{
    protected $casts = [
        'start_periode' => 'date',
        'end_periode' => 'date',
        'metabase_params' => 'array',
    ];
}
