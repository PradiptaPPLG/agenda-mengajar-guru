<?php

namespace App\Models;

use Database\Factories\GuruProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuruProfile extends Model
{
    /** @use HasFactory<GuruProfileFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'nip', 'kaprog_jurusan'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
