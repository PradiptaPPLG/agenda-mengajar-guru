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
                <form method="POST" action="{{ route('logout') }}" onsubmit="event.preventDefault(); window.dispatchEvent(new CustomEvent('open-logout-modal', { detail: { form: this } }));">
                    @csrf
                    <button type="submit" class="p-2 rounded-lg text-slate-500 hover:bg-slate-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </form>
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
                <a href="{{ route('guru.dashboard') }}"
                   class="flex flex-col items-center gap-0.5 px-4 py-2 rounded-xl transition-colors {{ request()->routeIs('guru.dashboard') ? 'text-blue-600' : 'text-slate-500 hover:text-slate-900' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span class="text-xs font-medium">Jadwal</span>
                </a>
                <a href="{{ route('profile.index') }}"
                   class="flex flex-col items-center gap-0.5 px-4 py-2 rounded-xl transition-colors {{ request()->routeIs('profile.*') ? 'text-indigo-600' : 'text-slate-500 hover:text-slate-900' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <span class="text-xs font-medium">Profil</span>
                </a>
            </div>
        </nav>
    </div>
</x-layouts.app>
