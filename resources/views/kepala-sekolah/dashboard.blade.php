<x-layouts.kepala-sekolah>
    <x-slot:title>Dashboard Kepala Sekolah</x-slot:title>

    <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
        @foreach([
            ['label' => 'Guru Hadir Hari Ini', 'value' => $stats['guru_hadir_hari_ini'], 'color' => 'emerald'],
            ['label' => 'Guru Sakit', 'value' => $stats['guru_sakit_hari_ini'], 'color' => 'amber'],
            ['label' => 'Guru Alpa', 'value' => $stats['guru_alpa_hari_ini'], 'color' => 'red'],
            ['label' => 'Total Guru', 'value' => $stats['total_guru'], 'color' => 'blue'],
            ['label' => 'Pertemuan Hari Ini', 'value' => $stats['pertemuan_hari_ini'], 'color' => 'violet'],
        ] as $stat)
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <p class="text-2xl font-bold text-{{ $stat['color'] }}-600">{{ $stat['value'] }}</p>
            <p class="text-sm text-slate-500 mt-1">{{ $stat['label'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- Chart Section --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-5 mb-6">
        <h3 class="font-semibold text-slate-900 mb-4">Statistik Kehadiran Guru (7 Hari Terakhir)</h3>
        <div class="w-full h-64">
            <canvas id="kehadiranChart"></canvas>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
            <h3 class="font-semibold text-slate-900">Kehadiran Terbaru (7 Hari)</h3>
            <a href="{{ route('kepala-sekolah.report.guru') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">Lihat semua →</a>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Guru</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600 hidden md:table-cell">Mata Pelajaran</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600 hidden md:table-cell">Kelas</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Status</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600 hidden lg:table-cell">Waktu</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($recentKehadiran as $kh)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3 font-medium text-slate-900">{{ $kh->guru->name }}</td>
                    <td class="px-4 py-3 text-slate-500 hidden md:table-cell">{{ $kh->pertemuan?->jadwal?->mataPelajaran?->nama ?? '—' }}</td>
                    <td class="px-4 py-3 text-slate-500 hidden md:table-cell">{{ $kh->pertemuan?->jadwal?->kelas?->nama ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <span class="text-xs font-medium px-2.5 py-1 rounded-full
                            {{ $kh->status === 'hadir' ? 'badge-hadir' : ($kh->status === 'sakit' ? 'badge-sakit' : 'badge-alpa') }}">
                            {{ $kh->status_label }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-slate-500 text-xs hidden lg:table-cell">{{ $kh->created_at->format('d/m H:i') }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400 text-sm">Belum ada data kehadiran</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @push('scripts')
    <script>
        const ctx = document.getElementById('kehadiranChart').getContext('2d');
        const chartData = @json($chartData);
        
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: chartData.labels,
                datasets: [{
                    data: chartData.data,
                    backgroundColor: [
                        '#10b981', // Hadir - emerald
                        '#f59e0b', // Sakit - amber
                        '#ef4444', // Alpa - red
                        '#a855f7'  // Dispensasi - purple
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                let value = context.parsed;
                                let percentage = total > 0 ? Math.round((value / total) * 100) + '%' : '0%';
                                return label + percentage + ' (' + value + ')';
                            }
                        }
                    }
                }
            }
        });
    </script>
    @endpush
</x-layouts.kepala-sekolah>
