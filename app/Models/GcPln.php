<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GcPln extends Model
{
    protected $connection = 'fasih';
    protected $table = 'GC_PLN';
    
    // Nonaktifkan default Laravel timestamps jika berbeda namanya,
    // (Namun Python app.py mendefinisikan `created_at` dan `updated_at`,
    // sehingga kompatibel 100% dengan standar Laravel)
    public $timestamps = false; // Set true jika ingin di-manage sebagian oleh Laravel, tapi lebih baik false karena Python menggunakan fungsi ON UPDATE CURRENT_TIMESTAMP MySQL.

    protected $guarded = [];
}
