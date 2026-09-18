<?php

namespace App\Models;

use Database\Factories\SiswaProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiswaProfile extends Model
{
    /** @use HasFactory<SiswaProfileFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'nis', 'kelas_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }
}
