<x-layouts.admin>
    <x-slot:title>Tambah Pengguna Baru</x-slot:title>

    <div class="max-w-4xl pb-8">
        {{-- Header Navigation --}}
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-sm text-slate-500 mb-1">
                    <a href="{{ route('admin.pengguna.index') }}" class="hover:text-blue-600 transition-colors">Pengguna & Akses</a>
                    <span>/</span>
                    <span class="text-slate-700 font-medium">Tambah Akun</span>
                </div>
                <h1 class="text-xl font-bold text-slate-900">Tambah Akun Pengguna Baru</h1>
                <p class="text-xs text-slate-500 mt-0.5">Buat akun untuk Pengawas Sekolah, Kepala Sekolah, Admin, Guru, atau staf lainnya.</p>
            </div>
            <a href="{{ route('admin.pengguna.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-700 bg-white px-3.5 py-2 border border-slate-300 rounded-xl shadow-xs hover:bg-slate-50 transition-colors shrink-0">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Kembali
            </a>
        </div>

        <form action="{{ route('admin.pengguna.store') }}" method="POST" class="space-y-6">
            @csrf

            {{-- Card 1: Data Identitas & Role --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-5">
                <div class="flex items-center gap-3 pb-3 border-b border-slate-100">
                    <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </div>
                    <div>
                        <h2 class="font-bold text-slate-900 text-sm">Informasi Akun & Peran</h2>
                        <p class="text-xs text-slate-500">Nama lengkap, NIP identitas, peran sistem, dan email.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    {{-- Nama Lengkap --}}
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Nama Lengkap & Gelar <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                               placeholder="Contoh: Ika Juliatiningsih, S.Pd., M.Pd"
                               class="w-full px-3.5 py-2.5 border {{ $errors->has('name') ? 'border-red-400' : 'border-slate-200' }} rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                        @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- Role Sistem Utama --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Role Sistem Utama <span class="text-red-500">*</span></label>
                        <select name="role" required id="role-select"
                                class="w-full px-3.5 py-2.5 border {{ $errors->has('role') ? 'border-red-400' : 'border-slate-200' }} rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50 font-medium text-slate-900">
                            <option value="">Pilih role pengguna...</option>
                            <option value="pengawas" {{ old('role') === 'pengawas' ? 'selected' : '' }}>Pengawas Sekolah (Monitoring Setara Kepsek)</option>
                            <option value="kepala_sekolah" {{ old('role') === 'kepala_sekolah' ? 'selected' : '' }}>Kepala Sekolah (Monitoring KBM & Rekap)</option>
                            <option value="guru" {{ old('role') === 'guru' ? 'selected' : '' }}>Guru (Pendidik / Pengampu Jadwal)</option>
                            <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin Sekolah</option>
                            @if(auth()->user()->isSuperAdmin())
                                <option value="super_admin" {{ old('role') === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                            @endif
                            <option value="piket" {{ old('role') === 'piket' ? 'selected' : '' }}>Petugas Piket</option>
                            <option value="tu" {{ old('role') === 'tu' ? 'selected' : '' }}>Tata Usaha (TU)</option>
                        </select>
                        <p class="text-[11px] text-slate-400 mt-1">Menentukan tampilan dashboard saat login (Pengawas & Kepala Sekolah memiliki dashboard monitoring).</p>
                        @error('role')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- NIP --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">NIP (Nomor Induk Pegawai)</label>
                        <input type="text" name="nip" value="{{ old('nip') }}"
                               placeholder="Contoh: 196907121998022001"
                               class="w-full px-3.5 py-2.5 border {{ $errors->has('nip') ? 'border-red-400' : 'border-slate-200' }} rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono bg-white">
                        <p class="text-[11px] text-slate-400 mt-1">Dapat digunakan pengguna untuk login langsung dengan NIP.</p>
                        @error('nip')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- Email --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Email Pengguna <span class="text-xs text-slate-400 font-normal">(opsional jika ada NIP)</span></label>
                        <input type="email" name="email" value="{{ old('email') }}"
                               placeholder="Contoh: pengguna@sekolah.sch.id"
                               class="w-full px-3.5 py-2.5 border {{ $errors->has('email') ? 'border-red-400' : 'border-slate-200' }} rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                        <p class="text-[11px] text-slate-400 mt-1">Bila dikosongkan dan NIP diisi, email dibuat otomatis [NIP]@sekolah.sch.id.</p>
                        @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- Jabatan / Instansi / Keterangan --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Jabatan / Satuan Tugas / Instansi</label>
                        <input type="text" name="keterangan_jabatan" value="{{ old('keterangan_jabatan') }}"
                               placeholder="Contoh: Pengawas Pembina/Cabang Dinas Pendidikan Wilayah XIII"
                               class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                        <p class="text-[11px] text-slate-400 mt-1">Keterangan jabatan atau instansi asal pembina.</p>
                        @error('keterangan_jabatan')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- Status Akun --}}
                    <div class="md:col-span-2 flex items-center gap-3 pt-2">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                            <span class="ml-3 text-sm font-medium text-slate-700">Akun Aktif (Bisa Langsung Digunakan Login)</span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Card 2: Keamanan / Password --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-4">
                <div class="flex items-center gap-3 pb-3 border-b border-slate-100">
                    <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </div>
                    <div>
                        <h2 class="font-bold text-slate-900 text-sm">Kata Sandi (Password)</h2>
                        <p class="text-xs text-slate-500">Atur password login untuk akun ini.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Password <span class="text-xs text-slate-400 font-normal">(opsional)</span></label>
                        <input type="password" name="password" placeholder="Kosongkan untuk default otomatis"
                               class="w-full px-3.5 py-2.5 border {{ $errors->has('password') ? 'border-red-400' : 'border-slate-200' }} rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                        <p class="text-[11px] text-slate-400 mt-1">💡 <strong>Otomatis:</strong> Jika NIP diisi, password default adalah <strong>NIP</strong>. Jika NIP kosong, password default adalah <code class="text-slate-600 bg-slate-100 px-1 py-0.5 rounded">password123</code>.</p>
                        @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            {{-- Card 3: Akses Tambahan (Custom Spatie Roles) --}}
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
                <div class="bg-slate-50 px-6 py-4 border-b border-slate-200">
                    <h3 class="font-bold text-slate-800 text-sm">Akses Tambahan (Custom Roles)</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Berikan wewenang khusus tambahan (misal: Wali Kelas, Guru BK, Petugas Piket, Kaprog, dll).</p>
                </div>
                
                <div class="p-6 bg-slate-50/40">
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3.5">
                        @foreach($roles as $spatieRole)
                            <div class="relative flex items-start border border-slate-200 p-3 rounded-xl bg-white hover:border-blue-400 transition-colors shadow-2xs">
                                <div class="flex h-5 items-center">
                                    <input id="role_{{ $spatieRole->id }}" name="spatie_roles[]" value="{{ $spatieRole->name }}" type="checkbox"
                                           {{ in_array($spatieRole->name, old('spatie_roles', [])) ? 'checked' : '' }}
                                           class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-600">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="role_{{ $spatieRole->id }}" class="font-medium text-slate-700 cursor-pointer select-none">{{ $spatieRole->name }}</label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if($roles->isEmpty())
                        <p class="text-sm text-slate-500 italic">Belum ada role tambahan khusus yang dibuat.</p>
                    @endif
                    @error('spatie_roles')
                        <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('admin.pengguna.index') }}" class="px-5 py-2.5 border border-slate-200 text-slate-700 text-sm font-semibold rounded-xl hover:bg-slate-50 transition-colors">
                    Batal
                </a>
                <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl shadow-xs transition-colors">
                    Simpan Akun Pengguna
                </button>
            </div>
        </form>
    </div>
</x-layouts.admin>
