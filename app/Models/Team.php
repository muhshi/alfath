<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    protected $casts = [
        'name' => 'string',
    ];

    protected $fillable = [
        'name',
    ];

    public function surveys()
    {
        return $this->hasMany(Survey::class);
    }
}
