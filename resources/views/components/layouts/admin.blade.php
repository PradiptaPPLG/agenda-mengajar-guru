<x-layouts.app>
    <x-slot:title>{{ $title ?? 'Admin' }}</x-slot:title>

    <div class="flex h-screen overflow-hidden bg-slate-50">
        {{-- Sidebar --}}
        <aside id="sidebar" class="hidden lg:flex flex-col w-64 bg-white border-r border-slate-200 shrink-0">
            {{-- Logo --}}
            <div class="flex items-center gap-3 px-5 py-4 border-b border-slate-200">
                <div class="w-10 h-10 flex items-center justify-center shrink-0">
                    <img src="{{ asset('images/logo_new.png') }}" alt="Logo" class="w-full h-full object-contain">
                </div>
                <div>
                    <p class="text-sm font-bold text-slate-900 leading-tight">{{ \App\Models\Setting::get('school_name', 'Agenda Mengajar') }}</p>
                    <p class="text-xs text-slate-500">{{ auth()->user()->role_label }}</p>
                </div>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                @php $isAdmin = in_array(auth()->user()->role, ['admin', 'super_admin']); @endphp

                <x-admin-nav-link href="{{ route('admin.dashboard') }}" :active="request()->routeIs('admin.dashboard')">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    Dashboard
                </x-admin-nav-link>

                <div class="pt-2 pb-1">
                    <p class="px-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Manajemen</p>
                </div>

                <x-admin-nav-link href="{{ route('admin.users.index') }}" :active="request()->routeIs('admin.users.*')">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    Pengguna
                </x-admin-nav-link>

                <x-admin-nav-link href="{{ route('admin.siswa.index') }}" :active="request()->routeIs('admin.siswa.*')">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    Siswa
                </x-admin-nav-link>

                <x-admin-nav-link href="{{ route('admin.kelas.index') }}" :active="request()->routeIs('admin.kelas.*')">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    Kelas
                </x-admin-nav-link>

                <x-admin-nav-link href="{{ route('admin.mata-pelajaran.index') }}" :active="request()->routeIs('admin.mata-pelajaran.*')">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    Mata Pelajaran
                </x-admin-nav-link>

                <x-admin-nav-link href="{{ route('admin.jadwal.index') }}" :active="request()->routeIs('admin.jadwal.*')">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Jadwal
                </x-admin-nav-link>

                @if(auth()->user()->isSuperAdmin())
                <div class="pt-2 pb-1">
                    <p class="px-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Super Admin</p>
                </div>
                <x-admin-nav-link href="{{ route('kepala-sekolah.report.guru') }}" :active="false">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    Laporan
                </x-admin-nav-link>
                <x-admin-nav-link href="{{ route('super-admin.settings') }}" :active="request()->routeIs('super-admin.settings')">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Pengaturan
                </x-admin-nav-link>
                @endif
            </nav>

            {{-- User info + logout --}}
            <div class="px-3 py-3 border-t border-slate-200">
                <div class="flex items-center gap-3 px-2 py-2">
                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
                        <span class="text-xs font-bold text-blue-700">{{ substr(auth()->user()->name, 0, 1) }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-900 truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-slate-500 truncate">{{ auth()->user()->email }}</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors" title="Logout">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        {{-- Main content --}}
        <div class="flex flex-col flex-1 overflow-hidden">
            {{-- Mobile topbar --}}
            <header class="lg:hidden flex items-center justify-between px-4 h-14 bg-white border-b border-slate-200 shrink-0">
                <button id="sidebar-toggle" class="p-2 rounded-lg text-slate-500 hover:bg-slate-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <span class="text-sm font-semibold text-slate-900">{{ $title ?? 'Admin' }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="p-2 rounded-lg text-slate-500 hover:bg-slate-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </button>
                </form>
            </header>

            {{-- Page content --}}
            <main class="flex-1 overflow-y-auto">
                {{-- Page header --}}
                <div class="px-6 py-5 bg-white border-b border-slate-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-xl font-bold text-slate-900">{{ $title ?? 'Dashboard' }}</h1>
                            @isset($subtitle)
                            <p class="mt-0.5 text-sm text-slate-500">{{ $subtitle }}</p>
                            @endisset
                        </div>
                        @isset($actions)
                        <div class="flex items-center gap-2">{{ $actions }}</div>
                        @endisset
                    </div>
                </div>

                {{-- Flash messages --}}
                @if(session('success'))
                <div class="mx-6 mt-4 px-4 py-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-sm flex items-start gap-2">
                    <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    {{ session('success') }}
                </div>
                @endif
                @if(session('error'))
                <div class="mx-6 mt-4 px-4 py-3 bg-red-50 border border-red-200 text-red-800 rounded-xl text-sm flex items-start gap-2">
                    <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    {{ session('error') }}
                </div>
                @endif

                <div class="p-6">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>

    @push('scripts')
    <script>
        document.getElementById('sidebar-toggle')?.addEventListener('click', () => {
            document.getElementById('sidebar')?.classList.toggle('hidden');
        });
    </script>
    @endpush
</x-layouts.app>
