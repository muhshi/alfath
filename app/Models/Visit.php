<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    protected $fillable = [
        'survey_id',
        'ip_address',
        'path',
        'user_agent',
    ];

    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }
}
