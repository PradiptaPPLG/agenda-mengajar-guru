<?php

namespace App\Services;

use App\Models\HariLibur;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HariLiburSyncService
{
    /**
     * Synchronize national holidays for a given year from public API.
     *
     * @return array{success: bool, imported: int, updated: int, message: string}
     */
    public function syncTahun(int $year): array
    {
        $holidays = $this->fetchFromApis($year);

        if (empty($holidays)) {
            return [
                'success' => false,
                'imported' => 0,
                'updated' => 0,
                'message' => "Tidak dapat mengambil data hari libur untuk tahun {$year} dari API.",
            ];
        }

        $imported = 0;
        $updated = 0;

        foreach ($holidays as $item) {
            $date = Carbon::parse($item['tanggal'])->toDateString();
            $keterangan = trim($item['keterangan']);
            $jenis = $item['is_cuti'] ? 'cuti_bersama' : 'nasional';

            $existing = HariLibur::whereDate('tanggal', $date)->first();

            if ($existing) {
                // If it exists, update details if different
                if ($existing->keterangan !== $keterangan || $existing->jenis !== $jenis) {
                    $existing->update([
                        'keterangan' => $keterangan,
                        'jenis' => $jenis,
                    ]);
                    $updated++;
                }
            } else {
                HariLibur::create([
                    'tanggal' => $date,
                    'keterangan' => $keterangan,
                    'jenis' => $jenis,
                ]);
                $imported++;
            }
        }

        HariLibur::flushCache();

        return [
            'success' => true,
            'imported' => $imported,
            'updated' => $updated,
            'message' => "Sinkronisasi berhasil: {$imported} hari libur baru ditambahkan, {$updated} diperbarui untuk tahun {$year}.",
        ];
    }

    /**
     * Fetch from primary or fallback API.
     *
     * @return array<int, array{tanggal: string, keterangan: string, is_cuti: bool}>
     */
    protected function fetchFromApis(int $year): array
    {
        // 1. Try Primary API: dayoffapi.vercel.app
        try {
            $response = Http::timeout(6)->get("https://dayoffapi.vercel.app/api?year={$year}");
            if ($response->successful()) {
                $data = $response->json();
                if (is_array($data) && count($data) > 0) {
                    $results = [];
                    foreach ($data as $row) {
                        if (! empty($row['tanggal']) && ! empty($row['keterangan'])) {
                            $results[] = [
                                'tanggal' => $row['tanggal'],
                                'keterangan' => $row['keterangan'],
                                'is_cuti' => ! empty($row['is_cuti']),
                            ];
                        }
                    }
                    if (count($results) > 0) {
                        return $results;
                    }
                }
            }
        } catch (Exception $e) {
            Log::warning('HariLiburSyncService Primary API error: '.$e->getMessage());
        }

        // 2. Try Fallback API: api-harilibur.vercel.app
        try {
            $response = Http::timeout(6)->get("https://api-harilibur.vercel.app/api?year={$year}");
            if ($response->successful()) {
                $data = $response->json();
                if (is_array($data) && count($data) > 0) {
                    $results = [];
                    foreach ($data as $row) {
                        $date = $row['holiday_date'] ?? $row['tanggal'] ?? null;
                        $name = $row['holiday_name'] ?? $row['keterangan'] ?? null;
                        $isCuti = ! empty($row['is_cuti']) || str_contains(strtolower($name ?? ''), 'cuti bersama');

                        if ($date && $name) {
                            $results[] = [
                                'tanggal' => $date,
                                'keterangan' => $name,
                                'is_cuti' => $isCuti,
                            ];
                        }
                    }
                    if (count($results) > 0) {
                        return $results;
                    }
                }
            }
        } catch (Exception $e) {
            Log::warning('HariLiburSyncService Fallback API error: '.$e->getMessage());
        }

        return [];
    }
}
