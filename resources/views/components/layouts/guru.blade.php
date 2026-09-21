<x-layouts.app>
    <x-slot:title>{{ $title ?? 'Guru' }}</x-slot:title>

    <div class="flex flex-col min-h-screen bg-slate-50">
        {{-- Top bar --}}
        <header class="sticky top-0 z-40 bg-white border-b border-slate-200 shadow-sm">
            <div class="flex items-center justify-between px-4 h-14">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 flex items-center justify-center shrink-0">
                        <img src="{{ asset('images/logo_new.png') }}" alt="Logo" class="w-full h-full object-contain">
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-900 leading-tight">{{ $title ?? 'Dashboard' }}</p>
                        <p class="text-xs text-slate-500 leading-tight">{{ auth()->user()->name }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-1">
                    <a href="{{ route('guru.notifications.index') }}" class="relative p-2 rounded-lg text-slate-500 hover:bg-slate-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        @if(auth()->user()->unreadNotifications->count() > 0)
                        <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full border border-white"></span>
                        @endif
                    </a>
                    <form method="POST" action="{{ route('logout') }}" onsubmit="event.preventDefault(); window.dispatchEvent(new CustomEvent('open-logout-modal', { detail: { form: this } }));">
                        @csrf
                        <button type="submit" class="p-2 rounded-lg text-slate-500 hover:bg-slate-100 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        {{-- Flash messages --}}
        @if(session('success'))
        <div class="mx-4 mt-3 px-4 py-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-sm flex items-start gap-2">
            <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            {{ session('success') }}
        </div>
        @endif
        @if(session('error'))
        <div class="mx-4 mt-3 px-4 py-3 bg-red-50 border border-red-200 text-red-800 rounded-xl text-sm flex items-start gap-2">
            <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            {{ session('error') }}
        </div>
        @endif

        {{-- Content --}}
        <main class="flex-1 pb-safe">
            {{ $slot }}
        </main>

        {{-- Bottom Navigation --}}
        <nav class="fixed bottom-0 left-0 right-0 z-40 bg-white border-t border-slate-200 safe-area-bottom">
            <div class="flex items-center justify-around px-2 h-16" style="padding-bottom: env(safe-area-inset-bottom)">
                @php
                    $u = auth()->user();
                    $isWaliKelas = $u->hasAnyRole(['Wali Kelas', 'wali_kelas', 'wali-kelas', 'Wali'])
                        || $u->can('walikelas.view_rekap')
                        || $u->roles->contains(fn($r) => str_contains(strtolower($r->name), 'wali'))
                        || \App\Models\Kelas::where('wali_kelas_id', $u->id)->exists();

                    $isBk = $u->hasAnyRole(['Guru BK', 'BK', 'guru_bk', 'guru-bk'])
                        || $u->can('bk.view_rekap')
                        || $u->roles->contains(fn($r) => str_contains(strtolower($r->name), 'bk'))
                        || \App\Models\Kelas::where('bk_id', $u->id)->exists();

                    $isKaprog = $u->hasAnyRole(['Kaprog', 'kaprog', 'Kepala Program'])
                        || $u->can('kaprog.view_rekap')
                        || !empty($u->guruProfile?->kaprog_jurusan);
                @endphp
                
                <a href="{{ route('guru.dashboard') }}"
                   class="flex flex-col items-center gap-0.5 px-3 py-2 rounded-xl transition-colors {{ request()->routeIs('guru.dashboard', 'guru.pertemuan.*') ? 'text-blue-600' : 'text-slate-500 hover:text-slate-900' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span class="text-[10px] font-medium">Jadwal</span>
                </a>
                
                @if($isWaliKelas)
                <div class="flex flex-col items-center justify-center -mt-6">
                    <a href="{{ route('guru.wali-kelas.index') }}"
                       class="flex items-center justify-center w-12 h-12 bg-gradient-to-br from-amber-500 to-orange-600 text-white rounded-full shadow-lg shadow-amber-500/40 border-[3px] border-white {{ request()->routeIs('guru.wali-kelas.*') ? 'ring-2 ring-amber-300 ring-offset-1' : '' }} transition-transform active:scale-95">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </a>
                    <span class="mt-1 text-[10px] font-bold text-amber-600">Wali Kelas</span>
                </div>
                @endif
                
                @if($isBk)
                <div class="flex flex-col items-center justify-center -mt-6">
                    <a href="{{ route('guru.bk.index') }}"
                       class="flex items-center justify-center w-12 h-12 bg-blue-600 text-white rounded-full shadow-lg shadow-blue-500/40 border-[3px] border-white {{ request()->routeIs('guru.bk.*') ? 'ring-2 ring-blue-300 ring-offset-1' : '' }} transition-transform active:scale-95">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </a>
                    <span class="mt-1 text-[10px] font-bold text-blue-600">Guru BK</span>
                </div>
                @endif

                @if($isKaprog)
                <div class="flex flex-col items-center justify-center -mt-6">
                    <a href="{{ route('guru.kaprog.index') }}"
                       class="flex items-center justify-center w-12 h-12 bg-gradient-to-br from-purple-500 to-indigo-600 text-white rounded-full shadow-lg shadow-purple-500/40 border-[3px] border-white {{ request()->routeIs('guru.kaprog.*') ? 'ring-2 ring-purple-300 ring-offset-1' : '' }} transition-transform active:scale-95">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0V11m0 0l-2 2m2-2l2 2"/>
                        </svg>
                    </a>
                    <span class="mt-1 text-[10px] font-bold text-purple-600">Kaprog</span>
                </div>
                @endif
                
                <a href="{{ route('profile.index') }}"
                   class="flex flex-col items-center gap-0.5 px-3 py-2 rounded-xl transition-colors {{ request()->routeIs('profile.*') ? 'text-indigo-600' : 'text-slate-500 hover:text-slate-900' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <span class="text-[10px] font-medium">Profil</span>
                </a>
            </div>
        </nav>
    </div>
</x-layouts.app>
