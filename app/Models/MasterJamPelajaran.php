<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterJamPelajaran extends Model
{
    use HasFactory;

    protected $fillable = [
        'jam_ke',
        'jam_mulai',
        'jam_selesai',
    ];
}
