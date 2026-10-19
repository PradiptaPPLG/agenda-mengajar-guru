<x-layouts.admin>
    <x-slot:title>Audit Trail & Activity Log</x-slot:title>

    <div class="space-y-6" x-data="{
        detailModal: false,
        activeLog: null,
        showDetail(log) {
            this.activeLog = log;
            this.detailModal = true;
        }
    }">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-slate-900 flex items-center gap-2">
                    <span class="p-1.5 rounded-lg bg-blue-100 text-blue-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </span>
                    Audit Trail & Log Aktivitas Sistem
                </h1>
                <p class="text-xs text-slate-500 mt-1">Rekam jejak digital seluruh mutasi data (CREATE, UPDATE, DELETE) dan riwayat otentikasi pengguna secara permanen.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="px-3 py-1.5 rounded-xl bg-slate-100 text-slate-700 text-xs font-semibold border border-slate-200">
                    @if(method_exists($logs, 'total'))
                        Total: {{ number_format($logs->total()) }} Log Aktivitas
                    @else
                        Halaman {{ $logs->currentPage() }} ({{ $logs->count() }} Data Ditampilkan)
                    @endif
                </span>
            </div>
        </div>

        <!-- Category Tabs Bar -->
        <div class="flex flex-wrap items-center gap-2 p-1.5 bg-slate-100/80 rounded-2xl border border-slate-200/80">
            <a href="{{ route('super-admin.activity-logs.index', request()->except('kategori', 'page')) }}" 
               class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all flex items-center gap-1.5 {{ !request('kategori') ? 'bg-white text-slate-900 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50' }}">
                <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                <span>Semua Log</span>
            </a>
            @foreach($categories as $catKey => $cat)
            <a href="{{ route('super-admin.activity-logs.index', array_merge(request()->except('page'), ['kategori' => $catKey])) }}" 
               class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all flex items-center gap-1.5 {{ request('kategori') === $catKey ? 'bg-white text-slate-900 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50' }}">
                <span class="w-2 h-2 rounded-full {{ $cat['dot'] }}"></span>
                <span>{{ $cat['label'] }}</span>
            </a>
            @endforeach
        </div>

        @if($activeCategory)
        <div class="p-3.5 rounded-xl border {{ $activeCategory['badge'] }} flex items-start gap-3 text-xs">
            <span class="w-2.5 h-2.5 rounded-full {{ $activeCategory['dot'] }} mt-1 shrink-0"></span>
            <div>
                <strong class="font-bold">{{ $activeCategory['label'] }}</strong>
                <p class="mt-0.5 opacity-90">{{ $activeCategory['description'] }}</p>
            </div>
        </div>
        @endif

        <!-- Filter Card -->
        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs">
            <form action="{{ route('super-admin.activity-logs.index') }}" method="GET" class="space-y-3">
                @if(request('kategori'))
                <input type="hidden" name="kategori" value="{{ request('kategori') }}">
                @endif
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2.5">
                    <!-- Search Input -->
                    <div class="lg:col-span-2 relative">
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama user, deskripsi, atau IP..."
                                class="w-full pl-9 pr-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors">
                    </div>

                    <!-- Action Filter -->
                    <div>
                        <select name="action" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                            <option value="">Semua Aksi</option>
                            @foreach($availableActions as $act)
                                @php
                                    $actVal = is_string($act) ? $act : (is_scalar($act) ? (string) $act : '');
                                @endphp
                                @if($actVal !== '')
                                <option value="{{ $actVal }}" {{ (is_array(request('action')) ? in_array($actVal, request('action')) : (string) request('action') === $actVal) ? 'selected' : '' }}>{{ $actVal }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <!-- Module Filter -->
                    <div>
                        <select name="module" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                            <option value="">Semua Modul</option>
                            @foreach($availableModules as $mod)
                                @php
                                    $modVal = is_string($mod) ? $mod : (is_scalar($mod) ? (string) $mod : '');
                                @endphp
                                @if($modVal !== '')
                                <option value="{{ $modVal }}" {{ (is_array(request('module')) ? in_array($modVal, request('module')) : (string) request('module') === $modVal) ? 'selected' : '' }}>{{ $modVal }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <!-- Role Filter -->
                    <div>
                        <select name="role" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                            <option value="">Semua Role</option>
                            @foreach($availableRoles as $r)
                                @php
                                    $rVal = is_string($r) ? $r : (is_scalar($r) ? (string) $r : '');
                                @endphp
                                @if($rVal !== '')
                                <option value="{{ $rVal }}" {{ (is_array(request('role')) ? in_array($rVal, request('role')) : (string) request('role') === $rVal) ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $rVal)) }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Date Range & Action Buttons -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2 border-t border-slate-100">
                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <span class="text-slate-500 text-[11px] font-medium">Rentang Tanggal:</span>
                        <input type="date" name="start_date" value="{{ request('start_date') }}"
                                class="px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-blue-500">
                        <span class="text-slate-400">s/d</span>
                        <input type="date" name="end_date" value="{{ request('end_date') }}"
                                class="px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-blue-500">
                    </div>

                    <div class="flex items-center gap-2">
                        @if(request()->hasAny(['search', 'action', 'module', 'role', 'start_date', 'end_date']))
                        <a href="{{ request('kategori') ? route('super-admin.activity-logs.index', ['kategori' => request('kategori')]) : route('super-admin.activity-logs.index') }}" 
                           class="px-3 py-1.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-semibold transition-colors">
                            Reset Filter
                        </a>
                        @endif
                        <button type="submit" 
                                class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition-colors shadow-xs flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                            Terapkan Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Activity Logs Table -->
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 text-xs font-semibold uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3.5">Waktu</th>
                            <th class="px-4 py-3.5">Pelaksana</th>
                            <th class="px-4 py-3.5">Aksi & Modul</th>
                            <th class="px-4 py-3.5">Deskripsi Aktivitas</th>
                            <th class="px-4 py-3.5">IP Address</th>
                            <th class="px-4 py-3.5 text-center">Perubahan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($logs as $log)
                        @php
                            $actionUpper = is_string($log->action) ? strtoupper($log->action) : '';
                            $actionColor = match($actionUpper) {
                                'CREATE' => 'emerald',
                                'UPDATE' => 'blue',
                                'DELETE' => 'red',
                                'LOGIN' => 'violet',
                                'IMPERSONATE' => 'amber',
                                'RESTORE' => 'teal',
                                default => 'slate',
                            };
                            $hasDiff = !empty($log->old_values) || !empty($log->new_values);
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="px-4 py-3">
                                <p class="text-xs font-bold text-slate-800">{{ $log->created_at->format('d M Y, H:i') }}</p>
                                <p class="text-[10px] text-slate-400 mt-0.5">{{ $log->created_at->diffForHumans() }}</p>
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-full bg-slate-200 text-slate-700 font-bold text-xs flex items-center justify-center shrink-0">
                                        {{ strtoupper(substr($log->user_name ?? 'S', 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-xs font-semibold text-slate-900 truncate">{{ $log->user_name ?? 'Sistem' }}</p>
                                        <span class="inline-block text-[10px] font-medium text-slate-500 capitalize">
                                            {{ str_replace('_', ' ', $log->role ?? 'system') }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-{{ $actionColor }}-50 text-{{ $actionColor }}-700 border border-{{ $actionColor }}-200">
                                        {{ is_string($log->action) ? $log->action : (is_scalar($log->action) ? (string) $log->action : '-') }}
                                    </span>
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                        {{ is_string($log->module) ? $log->module : (is_scalar($log->module) ? (string) $log->module : '-') }}
                                    </span>
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold border {{ $log->category_meta['badge'] }}">
                                        {{ $log->category_meta['label'] }}
                                    </span>
                                </div>
                            </td>

                            <td class="px-4 py-3">
                                <p class="text-xs text-slate-700 max-w-md truncate" title="{{ $log->description }}">
                                    {{ $log->description }}
                                </p>
                            </td>

                            <td class="px-4 py-3">
                                <span class="font-mono text-xs text-slate-500 bg-slate-50 px-2 py-0.5 rounded-md border border-slate-200">
                                    {{ $log->ip_address ?? '127.0.0.1' }}
                                </span>
                            </td>

                            <td class="px-4 py-3 text-center">
                                @if($hasDiff)
                                <button type="button" @click="showDetail({{ json_encode($log) }})"
                                        class="px-2.5 py-1 text-xs font-semibold bg-blue-50 text-blue-700 hover:bg-blue-100 rounded-lg transition-colors border border-blue-200 cursor-pointer flex items-center gap-1 mx-auto">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    Bandingkan
                                </button>
                                @else
                                <span class="text-[11px] text-slate-400 italic">-</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-3 text-slate-400">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </div>
                                <p class="text-sm font-semibold text-slate-700">Tidak ada log aktivitas yang cocok dengan kriteria filter.</p>
                                <p class="text-xs text-slate-400 mt-1">Coba ubah tanggal atau bersihkan filter pencarian Anda.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($logs->hasPages())
            <div class="px-5 py-4 border-t border-slate-200 bg-slate-50/50">
                {{ $logs->links() }}
            </div>
            @endif
        </div>

        <!-- Modal Detail / Diff Viewer -->
        <div x-show="detailModal" style="display: none;" 
             class="fixed inset-0 z-50 overflow-y-auto"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs" @click="detailModal = false"></div>

            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-3xl bg-white rounded-2xl p-6 shadow-2xl border border-slate-200 space-y-4"
                     @click.outside="detailModal = false">
                    <!-- Modal Header -->
                    <div class="flex items-start justify-between border-b border-slate-100 pb-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-xs font-bold uppercase tracking-wider"
                                      :class="{
                                          'bg-emerald-50 text-emerald-700 border border-emerald-200': activeLog?.action === 'CREATE',
                                          'bg-blue-50 text-blue-700 border border-blue-200': activeLog?.action === 'UPDATE',
                                          'bg-red-50 text-red-700 border border-red-200': activeLog?.action === 'DELETE',
                                          'bg-violet-50 text-violet-700 border border-violet-200': activeLog?.action === 'LOGIN',
                                          'bg-amber-50 text-amber-700 border border-amber-200': activeLog?.action === 'IMPERSONATE',
                                          'bg-teal-50 text-teal-700 border border-teal-200': activeLog?.action === 'RESTORE'
                                      }"
                                      x-text="activeLog?.action">
                                </span>
                                <span class="text-xs font-semibold text-slate-500" x-text="'Modul: ' + (activeLog?.module ?? '-')"></span>
                            </div>
                            <h3 class="text-base font-bold text-slate-900 mt-1" x-text="activeLog?.description"></h3>
                        </div>
                        <button type="button" @click="detailModal = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Meta Info Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-3 bg-slate-50 rounded-xl text-xs border border-slate-100">
                        <div>
                            <span class="block text-slate-400 text-[10px]">Pelaksana</span>
                            <span class="font-bold text-slate-800" x-text="activeLog?.user_name ?? 'Sistem'"></span>
                        </div>
                        <div>
                            <span class="block text-slate-400 text-[10px]">Role Akun</span>
                            <span class="font-medium text-slate-700 capitalize" x-text="activeLog?.role ?? 'system'"></span>
                        </div>
                        <div>
                            <span class="block text-slate-400 text-[10px]">IP Address</span>
                            <span class="font-mono text-slate-700" x-text="activeLog?.ip_address ?? '-'"></span>
                        </div>
                        <div>
                            <span class="block text-slate-400 text-[10px]">Waktu Eksekusi</span>
                            <span class="font-medium text-slate-700" x-text="activeLog?.created_at ? new Date(activeLog.created_at).toLocaleString('id-ID') : '-'"></span>
                        </div>
                    </div>

                    <!-- Diff Comparison -->
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-2 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                            Perbandingan Nilai Data (Old vs New)
                        </h4>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-80 overflow-y-auto">
                            <!-- Old Values -->
                            <div class="p-3 bg-rose-50/60 border border-rose-200 rounded-xl">
                                <span class="text-xs font-bold text-rose-800 block mb-2 flex items-center gap-1">
                                    <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                    Data Sebelum Diubah (Old)
                                </span>
                                <template x-if="activeLog?.old_values && Object.keys(activeLog.old_values).length > 0">
                                    <div class="space-y-1.5 font-mono text-[11px]">
                                        <template x-for="(val, key) in activeLog.old_values" :key="key">
                                            <div class="p-1.5 bg-white/80 rounded border border-rose-100 flex items-start justify-between gap-2">
                                                <span class="font-semibold text-slate-600" x-text="key + ':'"></span>
                                                <span class="text-rose-700 text-right truncate max-w-[200px]" x-text="typeof val === 'object' ? JSON.stringify(val) : val"></span>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="!activeLog?.old_values || Object.keys(activeLog.old_values).length === 0">
                                    <p class="text-[11px] text-slate-400 italic">Tidak ada data sebelumnya (Record Baru).</p>
                                </template>
                            </div>

                            <!-- New Values -->
                            <div class="p-3 bg-emerald-50/60 border border-emerald-200 rounded-xl">
                                <span class="text-xs font-bold text-emerald-800 block mb-2 flex items-center gap-1">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    Data Sesudah Diubah (New)
                                </span>
                                <template x-if="activeLog?.new_values && Object.keys(activeLog.new_values).length > 0">
                                    <div class="space-y-1.5 font-mono text-[11px]">
                                        <template x-for="(val, key) in activeLog.new_values" :key="key">
                                            <div class="p-1.5 bg-white/80 rounded border border-emerald-100 flex items-start justify-between gap-2">
                                                <span class="font-semibold text-slate-600" x-text="key + ':'"></span>
                                                <span class="text-emerald-700 text-right truncate max-w-[200px]" x-text="typeof val === 'object' ? JSON.stringify(val) : val"></span>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="!activeLog?.new_values || Object.keys(activeLog.new_values).length === 0">
                                    <p class="text-[11px] text-slate-400 italic">Data telah dihapus dari sistem.</p>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- User Agent footer -->
                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[10px] text-slate-400">
                        <span class="truncate max-w-md" :title="activeLog?.user_agent" x-text="'User Agent: ' + (activeLog?.user_agent ?? '-')"></span>
                        <button type="button" @click="detailModal = false" class="px-4 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg text-xs transition-colors">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
