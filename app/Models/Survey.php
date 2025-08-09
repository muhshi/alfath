<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Survey extends Model
{
    protected $casts = [
        'name' => 'string',
        'description' => 'string',
        'start_periode' => 'date',
        'end_periode' => 'date',
        'metabase_dashboard_id' => 'integer',
    ];

    protected $fillable = [
        'name',
        'description',
        'start_periode',
        'end_periode',
        'metabase_dashboard_id',
        'category_id',
        'team_id',
    ];

    function category()
    {
        return $this->belongsTo(Category::class);
    }

    function team()
    {
        return $this->belongsTo(Team::class);
    }

}
