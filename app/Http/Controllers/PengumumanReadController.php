<?php

namespace App\Http\Controllers;

use App\Models\Pengumuman;
use App\Models\PengumumanRead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PengumumanReadController extends Controller
{
    /**
     * Mark an announcement as read for the authenticated user.
     */
    public function markAsRead(Request $request, Pengumuman $pengumuman): JsonResponse|RedirectResponse
    {
        $userId = Auth::id();

        PengumumanRead::firstOrCreate([
            'pengumuman_id' => $pengumuman->id,
            'user_id' => $userId,
        ], [
            'read_at' => now(),
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Pengumuman telah ditandai sudah dibaca.',
            ]);
        }

        return back()->with('success', 'Pengumuman ditutup dan ditandai sudah dibaca.');
    }
}
