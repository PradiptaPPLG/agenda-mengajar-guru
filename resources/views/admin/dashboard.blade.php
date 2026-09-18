<x-layouts.admin>
    <x-slot:title>Dashboard Admin</x-slot:title>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach([
            ['label' => 'Total Guru', 'value' => $stats['total_guru'], 'color' => 'blue', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
            ['label' => 'Total Siswa', 'value' => $stats['total_siswa'], 'color' => 'emerald', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
            ['label' => 'Total Kelas', 'value' => $stats['total_kelas'], 'color' => 'violet', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
            ['label' => 'Pertemuan Hari Ini', 'value' => $stats['pertemuan_hari_ini'], 'color' => 'amber', 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
        ] as $stat)
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <div class="flex items-center justify-between">
                <div class="w-10 h-10 rounded-xl bg-{{ $stat['color'] }}-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-{{ $stat['color'] }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $stat['icon'] }}"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-slate-900 mt-3">{{ $stat['value'] }}</p>
            <p class="text-sm text-slate-500 mt-0.5">{{ $stat['label'] }}</p>
        </div>
        @endforeach
    </div>

    <div class="mt-6 mb-6">
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <h3 class="font-semibold text-slate-900 mb-4">Menu Cepat</h3>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                @foreach([
                    ['href' => route('admin.users.create'), 'label' => 'Tambah User', 'color' => 'blue'],
                    ['href' => route('admin.kelas.create'), 'label' => 'Tambah Kelas', 'color' => 'emerald'],
                    ['href' => route('admin.jadwal.create'), 'label' => 'Tambah Jadwal', 'color' => 'violet'],
                    ['href' => route('admin.mata-pelajaran.create'), 'label' => 'Tambah Mapel', 'color' => 'amber'],
                ] as $item)
                <a href="{{ $item['href'] }}"
                   class="flex items-center justify-center py-3 bg-{{ $item['color'] }}-50 hover:bg-{{ $item['color'] }}-100 text-{{ $item['color'] }}-700 text-sm font-medium rounded-xl border border-{{ $item['color'] }}-200 transition-colors">
                    {{ $item['label'] }}
                </a>
                @endforeach
            </div>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <h3 class="font-semibold text-slate-900 mb-4">Statistik Kehadiran Guru (7 Hari)</h3>
            <div class="w-full h-48">
                <canvas id="kehadiranChart"></canvas>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <h3 class="font-semibold text-slate-900 mb-4">Statistik Kehadiran Siswa (7 Hari)</h3>
            <div class="w-full h-48">
                <canvas id="kehadiranSiswaChart"></canvas>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Chart Kehadiran Guru
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

        // Chart Kehadiran Siswa
        const ctxSiswa = document.getElementById('kehadiranSiswaChart').getContext('2d');
        const chartDataSiswa = @json($chartDataSiswa);
        
        new Chart(ctxSiswa, {
            type: 'pie',
            data: {
                labels: chartDataSiswa.labels,
                datasets: [{
                    data: chartDataSiswa.data,
                    backgroundColor: [
                        '#10b981', // Hadir - emerald
                        '#f59e0b', // Sakit - amber
                        '#3b82f6', // Izin - blue
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
</x-layouts.admin>
