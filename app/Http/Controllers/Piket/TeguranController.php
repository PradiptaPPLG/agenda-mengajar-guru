<?php

namespace App\Http\Controllers\Piket;

use App\Http\Controllers\Controller;
use App\Models\JadwalPelajaran;
use App\Models\User;
use App\Notifications\TeguranNotification;
use Illuminate\Http\Request;

class TeguranController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'guru_id' => 'required|exists:users,id',
            'jadwal_id' => 'required|exists:jadwal_pelajarans,id',
        ]);

        $guru = User::findOrFail($request->guru_id);
        $jadwal = JadwalPelajaran::with(['kelas', 'mataPelajaran'])->findOrFail($request->jadwal_id);

        $message = 'Teguran Piket: Anda belum mengisi kehadiran untuk jadwal '.
                   ($jadwal->mataPelajaran->nama ?? '').' di kelas '.
                   ($jadwal->kelas->nama ?? '').' saat ini.';

        $guru->notify(new TeguranNotification($message, $jadwal));

        return back()->with('success', 'Teguran berhasil dikirim ke '.$guru->name);
    }
}
