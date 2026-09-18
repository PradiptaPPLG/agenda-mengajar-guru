@php
    $layout = auth()->user()->role === 'siswa' ? 'layouts.siswa' : (auth()->user()->role === 'guru' ? 'layouts.guru' : 'layouts.app');
@endphp
<x-dynamic-component :component="$layout">
    <x-slot:title>Profil Saya</x-slot:title>

    <div class="p-4 md:p-6 space-y-6 max-w-2xl mx-auto">
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center text-2xl border border-slate-200">
                    <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-slate-900">{{ auth()->user()->name }}</h2>
                    <p class="text-slate-500 capitalize">{{ str_replace('_', ' ', auth()->user()->role) }}</p>
                </div>
            </div>

            <h3 class="text-sm font-bold text-slate-900 mb-3 uppercase tracking-wider">Informasi Akun</h3>
            <div class="space-y-4 text-sm">
                <div>
                    <span class="block text-slate-500 mb-0.5">Email</span>
                    <span class="font-medium text-slate-900">{{ auth()->user()->email }}</span>
                </div>
                
                @if(auth()->user()->role === 'siswa' && auth()->user()->siswaProfile)
                <div>
                    <span class="block text-slate-500 mb-0.5">NIS</span>
                    <span class="font-medium text-slate-900">{{ auth()->user()->siswaProfile->nis ?? '-' }}</span>
                </div>
                <div>
                    <span class="block text-slate-500 mb-0.5">Kelas</span>
                    <span class="font-medium text-slate-900">{{ auth()->user()->siswaProfile->kelas->nama ?? 'Belum berkelas' }}</span>
                </div>
                @endif
                
                @if(auth()->user()->role === 'guru' && auth()->user()->guruProfile)
                <div>
                    <span class="block text-slate-500 mb-0.5">NIP</span>
                    <span class="font-medium text-slate-900">{{ auth()->user()->guruProfile->nip ?? '-' }}</span>
                </div>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
            <h3 class="text-sm font-bold text-slate-900 mb-4 uppercase tracking-wider">Ubah Password</h3>
            <form action="{{ route('profile.password.update') }}" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Password Saat Ini</label>
                    <input type="password" name="current_password" required
                           class="w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 px-4 py-2 text-sm bg-slate-50">
                    @error('current_password')
                        <p class="mt-1 text-xs text-red-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Password Baru</label>
                    <input type="password" name="password" required
                           class="w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 px-4 py-2 text-sm bg-slate-50">
                    @error('password')
                        <p class="mt-1 text-xs text-red-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Konfirmasi Password Baru</label>
                    <input type="password" name="password_confirmation" required
                           class="w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 px-4 py-2 text-sm bg-slate-50">
                </div>

                <div class="pt-2">
                    <button type="submit" 
                            class="w-full bg-slate-900 text-white font-semibold py-2.5 rounded-xl hover:bg-slate-800 transition-colors shadow-sm text-sm">
                        Simpan Password
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
            <h3 class="text-sm font-bold text-red-600 mb-4 uppercase tracking-wider">Keluar Aplikasi</h3>
            <p class="text-sm text-slate-500 mb-4">Pastikan pekerjaan Anda sudah tersimpan sebelum keluar dari aplikasi.</p>
            <form method="POST" action="{{ route('logout') }}" onsubmit="event.preventDefault(); window.dispatchEvent(new CustomEvent('open-logout-modal', { detail: { form: this } }));">
                @csrf
                <button type="submit" class="w-full bg-red-50 text-red-600 border border-red-200 font-semibold py-2.5 rounded-xl hover:bg-red-100 transition-colors shadow-sm text-sm flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Logout
                </button>
            </form>
        </div>
        
        <!-- Tambahan padding bawah agar tidak tertutup navbar -->
        <div class="h-10"></div>
    </div>
</x-dynamic-component>
