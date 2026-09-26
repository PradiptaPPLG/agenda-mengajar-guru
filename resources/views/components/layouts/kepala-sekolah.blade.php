<x-layouts.app>
    <x-slot:title>{{ $title ?? 'Kepala Sekolah' }}</x-slot:title>

    <div x-data="{ sidebarOpen: false }" class="flex h-screen overflow-hidden bg-slate-50">
        {{-- Mobile Backdrop Overlay --}}
        <div x-show="sidebarOpen" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="sidebarOpen = false" 
             style="display: none;"
             class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-xs lg:hidden">
        </div>

        {{-- Sidebar --}}
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
               class="fixed inset-y-0 left-0 z-50 flex flex-col w-64 bg-white border-r border-slate-200 shrink-0 transition-transform duration-300 ease-in-out lg:static lg:z-auto">
            
            {{-- Header Logo & Mobile Close Button --}}
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 flex items-center justify-center shrink-0">
                        <img src="{{ asset('images/logo_new.png') }}" alt="Logo" class="w-full h-full object-contain">
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-900 leading-tight">{{ \App\Models\Setting::get('school_name', 'Agenda Mengajar') }}</p>
                        <p class="text-xs text-slate-500">Kepala Sekolah</p>
                    </div>
                </div>

                {{-- Mobile Close Button --}}
                <button @click="sidebarOpen = false" class="lg:hidden p-1.5 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors" title="Tutup Menu">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                <x-admin-nav-link href="{{ route('kepala-sekolah.dashboard') }}" :active="request()->routeIs('kepala-sekolah.dashboard')">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    Dashboard
                </x-admin-nav-link>

                <div class="pt-2 pb-1">
                    <p class="px-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Laporan</p>
                </div>

                <x-admin-nav-link href="{{ route('kepala-sekolah.report.guru') }}" :active="request()->routeIs('kepala-sekolah.report.guru')">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    Kehadiran Guru
                </x-admin-nav-link>

                <x-admin-nav-link href="{{ route('kepala-sekolah.report.siswa') }}" :active="request()->routeIs('kepala-sekolah.report.siswa')">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Kehadiran Siswa
                </x-admin-nav-link>
            </nav>

            <div class="px-3 py-2">
                <a href="{{ route('panduan') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    Buku Panduan
                </a>
            </div>

            <div class="px-3 py-3 border-t border-slate-200">
                <div class="flex items-center gap-3 px-2 py-2">
                    <div class="w-8 h-8 rounded-full bg-violet-100 flex items-center justify-center shrink-0">
                        <span class="text-xs font-bold text-violet-700">{{ substr(auth()->user()->name, 0, 1) }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-900 truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-slate-500 truncate">Kepala Sekolah</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" onsubmit="event.preventDefault(); window.dispatchEvent(new CustomEvent('open-logout-modal', { detail: { form: this } }));">
                        @csrf
                        <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-red-600 transition-colors" title="Logout">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        {{-- Main content --}}
        <div class="flex flex-col flex-1 overflow-hidden min-w-0">
            <header class="lg:hidden flex items-center justify-between px-4 h-14 bg-white border-b border-slate-200 shrink-0">
                <button @click="sidebarOpen = true" class="flex items-center gap-2 p-2 rounded-lg text-slate-600 hover:bg-slate-100 transition-colors" title="Buka Menu Navigasi">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-600">Menu</span>
                </button>
                <div class="flex items-center gap-2 min-w-0">
                    <img src="{{ asset('images/logo_new.png') }}" alt="Logo" class="w-6 h-6 object-contain shrink-0">
                    <span class="text-xs font-bold text-slate-900 truncate max-w-[140px] sm:max-w-none">{{ $title ?? 'Kepala Sekolah' }}</span>
                </div>
                <div class="w-7 h-7 rounded-full bg-violet-100 text-violet-700 flex items-center justify-center font-bold text-xs shrink-0">
                    {{ substr(auth()->user()->name, 0, 1) }}
                </div>
            </header>

            <main class="flex-1 overflow-y-auto">
                <div class="px-4 sm:px-6 py-4 sm:py-5 bg-white border-b border-slate-200">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <h1 class="text-lg sm:text-xl font-bold text-slate-900 leading-tight">{{ $title ?? 'Dashboard' }}</h1>
                            @isset($subtitle)
                            <p class="mt-0.5 text-xs sm:text-sm text-slate-500">{{ $subtitle }}</p>
                            @endisset
                        </div>
                        @isset($actions)
                        <div class="flex flex-wrap items-center gap-2 shrink-0">{{ $actions }}</div>
                        @endisset
                    </div>
                </div>

                @if(session('success'))
                <div class="mx-4 sm:mx-6 mt-4 px-4 py-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-sm flex items-start gap-2">
                    <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    {{ session('success') }}
                </div>
                @endif

                <div class="p-4 sm:p-6">{{ $slot }}</div>
            </main>
        </div>
    </div>
</x-layouts.app>
