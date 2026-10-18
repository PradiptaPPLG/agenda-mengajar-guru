@php
    $user = auth()->user();
    $pengumumans = $user ? \App\Models\Pengumuman::active()
        ->forUser($user)
        ->unreadBy($user)
        ->orderByDesc('is_pinned')
        ->orderByDesc('created_at')
        ->get() : collect();
@endphp

@if($pengumumans->isNotEmpty())
    <div class="space-y-3 mb-5" id="pengumuman-broadcast-list">
        {{-- Push / Browser Notification Activation Prompt --}}
        <div x-data="{
                canAskNotif: false,
                dismissed: false,
                init() {
                    this.canAskNotif = ('Notification' in window && Notification.permission === 'default');
                    this.dismissed = localStorage.getItem('hide_notif_prompt') === '1';
                },
                async requestPerm() {
                    const perm = await Notification.requestPermission();
                    this.canAskNotif = (perm === 'default');
                    if (perm === 'granted') {
                        new Notification('Notifikasi Diaktifkan', {
                            body: 'Anda akan menerima pengumuman dan agenda sekolah langsung di perangkat ini.',
                            icon: '/images/logo_new.png'
                        });
                    }
                }
             }"
             x-show="canAskNotif && !dismissed"
             x-cloak
             class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 p-3 rounded-xl bg-blue-50/90 border border-blue-200 text-blue-900 text-xs shadow-xs">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                <span>Aktifkan notifikasi perangkat agar pengumuman penting langsung berdering di HP Anda.</span>
            </div>
            <div class="flex items-center gap-2 shrink-0 self-end sm:self-auto">
                <button type="button" @click="requestPerm" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg text-2xs transition-colors shadow-2xs cursor-pointer">
                    Izinkan Notifikasi
                </button>
                <button type="button" @click="dismissed = true; localStorage.setItem('hide_notif_prompt', '1')" class="text-slate-400 hover:text-slate-600 text-xs px-1 cursor-pointer" title="Tutup">
                    ✕
                </button>
            </div>
        </div>

        @foreach($pengumumans as $p)
            @php
                $themeClasses = match($p->tipe) {
                    'urgent' => [
                        'card' => 'bg-rose-50/95 border-rose-300 text-rose-950 shadow-rose-100',
                        'icon_bg' => 'bg-rose-600 text-white',
                        'badge' => 'bg-rose-100 text-rose-800 border-rose-200',
                        'btn_read' => 'text-rose-700 bg-white hover:bg-rose-100 border-rose-300',
                        'label' => 'MENDESAK',
                    ],
                    'penting' => [
                        'card' => 'bg-amber-50/95 border-amber-300 text-amber-950 shadow-amber-100',
                        'icon_bg' => 'bg-amber-600 text-white',
                        'badge' => 'bg-amber-100 text-amber-800 border-amber-200',
                        'btn_read' => 'text-amber-800 bg-white hover:bg-amber-100 border-amber-300',
                        'label' => 'PERHATIAN / PENTING',
                    ],
                    default => [
                        'card' => 'bg-blue-50/95 border-blue-200 text-blue-950 shadow-blue-100',
                        'icon_bg' => 'bg-blue-600 text-white',
                        'badge' => 'bg-blue-100 text-blue-800 border-blue-200',
                        'btn_read' => 'text-blue-700 bg-white hover:bg-blue-100 border-blue-200',
                        'label' => 'PENGUMUMAN',
                    ],
                };
            @endphp

            <div x-data="{
                    visible: true,
                    loading: false,
                    init() {
                        if ('Notification' in window && Notification.permission === 'granted') {
                            const seenKey = 'notif_seen_{{ $p->id }}';
                            if (!sessionStorage.getItem(seenKey)) {
                                sessionStorage.setItem(seenKey, '1');
                                const title = {{ json_encode($p->judul) }};
                                const body = {{ json_encode(\Illuminate\Support\Str::limit(strip_tags($p->isi), 100)) }};
                                if (navigator.serviceWorker && navigator.serviceWorker.controller) {
                                    navigator.serviceWorker.ready.then(reg => {
                                        reg.showNotification(title, {
                                            body: body,
                                            icon: '/images/logo_new.png',
                                            badge: '/images/logo_new.png',
                                            data: { url: window.location.href }
                                        });
                                    }).catch(() => {
                                        new Notification(title, { body: body, icon: '/images/logo_new.png' });
                                    });
                                } else {
                                    new Notification(title, { body: body, icon: '/images/logo_new.png' });
                                }
                            }
                        }
                    },
                    async markRead() {
                        this.loading = true;
                        try {
                            const res = await fetch('{{ route('pengumuman.mark-read', $p) }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]') ? document.querySelector('meta[name=csrf-token]').content : '{{ csrf_token() }}'
                                }
                            });
                            if (res.ok) {
                                this.visible = false;
                            } else {
                                this.loading = false;
                            }
                        } catch (e) {
                            this.loading = false;
                        }
                    }
                 }"
                 x-show="visible"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 transform scale-100"
                 x-transition:leave-end="opacity-0 transform scale-95"
                 class="rounded-2xl border p-4 shadow-sm {{ $themeClasses['card'] }}">

                <div class="flex items-start gap-3.5">
                    {{-- Icon Indicator --}}
                    <div class="w-10 h-10 rounded-xl {{ $themeClasses['icon_bg'] }} flex items-center justify-center shrink-0 shadow-xs mt-0.5">
                        @if($p->tipe === 'urgent')
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        @elseif($p->tipe === 'penting')
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @else
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                        @endif
                    </div>

                    {{-- Main Content --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border {{ $themeClasses['badge'] }}">
                                {{ $themeClasses['label'] }}
                            </span>

                            @if($p->is_pinned)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-amber-200 text-amber-900 border border-amber-300">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M5 4a1 1 0 00-2 0v7.268a2 2 0 00.868 1.652l3.414 2.276a1 1 0 001.436-.832V12h2v2.364a1 1 0 001.436.832l3.414-2.276A2 2 0 0017 11.268V4a1 1 0 00-2 0v6.172l-2 1.333V4a1 1 0 00-2 0v7.505l-2-1.333V4z"/></svg>
                                    PINNED
                                </span>
                            @endif

                            <span class="text-2xs text-slate-500">
                                {{ $p->created_at->diffForHumans() }} ({{ $p->created_at->format('d M Y') }})
                            </span>
                        </div>

                        <h3 class="text-sm font-bold text-slate-900 leading-snug">
                            {{ $p->judul }}
                        </h3>

                        <div class="text-xs text-slate-700 mt-1.5 leading-relaxed whitespace-pre-line">
                            {{ $p->isi }}
                        </div>

                        {{-- Action Buttons Row --}}
                        <div class="flex flex-wrap items-center justify-between gap-3 mt-3 pt-2.5 border-t border-slate-200/60">
                            <div>
                                @if($p->file_lampiran)
                                    <a href="{{ asset('storage/' . $p->file_lampiran) }}" target="_blank"
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 text-xs font-semibold text-slate-700 shadow-2xs transition-colors">
                                        <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <span>Lihat Lampiran: {{ $p->nama_file ?? 'Unduh Dokumen' }}</span>
                                    </a>
                                @endif
                            </div>

                            <button type="button" @click="markRead" :disabled="loading"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold border shadow-2xs transition-all cursor-pointer {{ $themeClasses['btn_read'] }}">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span x-text="loading ? 'Menyimpan...' : 'Tandai Sudah Dibaca'">Tandai Sudah Dibaca</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
