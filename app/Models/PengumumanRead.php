<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengumumanRead extends Model
{
    use HasFactory;

    protected $table = 'pengumuman_reads';

    protected $fillable = [
        'pengumuman_id',
        'user_id',
        'read_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function pengumuman(): BelongsTo
    {
        return $this->belongsTo(Pengumuman::class, 'pengumuman_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }
}
