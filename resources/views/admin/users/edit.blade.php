<x-layouts.admin>
    <x-slot:title>Edit Pengguna</x-slot:title>

    <div class="max-w-6xl pb-8">
        <form action="{{ route('admin.users.update', $user) }}" method="POST">
            @csrf @method('PUT')

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                {{-- Kolom Kiri: Informasi Dasar & Keamanan (5 Kolom) --}}
                <div class="lg:col-span-5 space-y-6">
                    {{-- Card 1: Data Identitas --}}
                    <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4 shadow-sm">
                        <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100">
                            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-900 text-sm">Informasi Akun</h3>
                                <p class="text-xs text-slate-500">Nama, NIP, email & role akun</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Nama Lengkap <span class="text-red-500">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                                   class="w-full px-3.5 py-2.5 border {{ $errors->has('name') ? 'border-red-400' : 'border-slate-200' }} rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div id="nip-field" class="{{ old('role', $user->role) === 'guru' ? '' : 'hidden' }}">
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">NIP (opsional)</label>
                            <input type="text" name="nip" value="{{ old('nip', $user->guruProfile?->nip) }}"
                                   placeholder="Contoh: 198109152006041043"
                                   class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Email <span id="email-required-mark" class="text-red-500 {{ old('role', $user->role) === 'siswa' ? 'hidden' : '' }}">*</span> <span id="email-optional-hint" class="text-slate-400 font-normal {{ old('role', $user->role) === 'siswa' ? '' : 'hidden' }}">(opsional untuk siswa)</span></label>
                            <input type="email" id="email-input" name="email" value="{{ old('email', $user->email) }}" {{ old('role', $user->role) === 'siswa' ? '' : 'required' }}
                                   class="w-full px-3.5 py-2.5 border {{ $errors->has('email') ? 'border-red-400' : 'border-slate-200' }} rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Role Sistem Utama <span class="text-red-500">*</span></label>
                            <select name="role" required onchange="handleRoleChange(this.value)"
                                    class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50">
                                @foreach(['guru' => 'Guru', 'piket' => 'Guru Piket', 'tu' => 'Tata Usaha (TU)', 'siswa' => 'Siswa'] as $val => $label)
                                <option value="{{ $val }}" {{ old('role', $user->role) === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Card 2: Keamanan / Password --}}
                    <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4 shadow-sm">
                        <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100">
                            <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-900 text-sm">Ganti Password</h3>
                                <p class="text-xs text-slate-500">Kosongkan jika tidak ingin diubah</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Password Baru</label>
                            <input type="password" name="password" placeholder="••••••••"
                                   class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Konfirmasi Password</label>
                            <input type="password" name="password_confirmation" placeholder="••••••••"
                                   class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                </div>

                {{-- Kolom Kanan: Hak Akses & Penugasan Peran (7 Kolom) --}}
                <div class="lg:col-span-7 space-y-6">
                    <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-5 shadow-sm">
                        <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100">
                            <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-900 text-sm">Hak Akses & Penugasan Kelas</h3>
                                <p class="text-xs text-slate-500">Pilih akses khusus dan kelas binaan guru</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Hak Akses / Permission Tambahan</label>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 p-4 bg-slate-50 border border-slate-200/80 rounded-xl">
                                @foreach($roles as $r)
                                    <label class="flex items-center p-2.5 bg-white border border-slate-200 rounded-xl hover:border-blue-300 transition-colors cursor-pointer shadow-2xs">
                                        <input type="checkbox" name="spatie_roles[]" value="{{ $r->name }}" id="role_{{ $r->id }}"
                                               {{ (is_array(old('spatie_roles')) && in_array($r->name, old('spatie_roles'))) || $user->hasRole($r->name) ? 'checked' : '' }}
                                               class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-600">
                                        <span class="ml-2.5 text-xs font-semibold text-slate-800">{{ $r->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <p class="mt-2 text-xs text-slate-500 leading-relaxed">
                                Centang <span class="font-semibold text-slate-700">Wali Kelas</span> atau <span class="font-semibold text-slate-700">Guru BK</span> untuk memunculkan pilihan penugasan kelas di bawah ini.
                            </p>
                        </div>

                        {{-- Penugasan Wali Kelas, BK, Kaprog & Mapel (Dinamis muncul jika role terkait dicentang) --}}
                        <div id="guru-fields" class="{{ old('role', $user->role) === 'guru' ? '' : 'hidden' }} space-y-4">
                            <div id="kaprog-jurusan-container" class="hidden p-4 border border-purple-200 bg-purple-50/50 rounded-xl space-y-2.5">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span>
                                    <label class="block text-sm font-bold text-purple-900">Penugasan Kaprog (Kepala Program Keahlian)</label>
                                </div>
                                <select name="kaprog_jurusan" class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 bg-white">
                                    <option value="">-- Pilih Jurusan Keahlian --</option>
                                    @foreach($daftarJurusan as $j)
                                        <option value="{{ $j }}" {{ old('kaprog_jurusan', $user->guruProfile?->kaprog_jurusan ?? '') == $j ? 'selected' : '' }}>{{ $j }}</option>
                                    @endforeach
                                </select>
                                <p class="text-[11px] text-purple-700 font-medium">Kaprog dapat memantau seluruh kelas pada jurusan ini (angkatan 10 - 12).</p>
                            </div>

                            <div id="wali-kelas-container" class="hidden p-4 border border-amber-200 bg-amber-50/50 rounded-xl space-y-2.5">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                                    <label class="block text-sm font-bold text-amber-900">Penugasan Wali Kelas (Pilih 1 Kelas)</label>
                                </div>
                                <select name="wali_kelas_id" class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 bg-white">
                                    <option value="">-- Pilih Kelas Binaan --</option>
                                    @foreach($kelas as $k)
                                        <option value="{{ $k->id }}" {{ old('wali_kelas_id', $assignedWaliKelas?->id) == $k->id ? 'selected' : '' }}>{{ $k->nama }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div id="bk-kelas-container" class="hidden p-4 border border-emerald-200 bg-emerald-50/50 rounded-xl space-y-2.5">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                        <label class="block text-sm font-bold text-emerald-900">Penugasan Guru BK (Pilih Kelas Binaan)</label>
                                    </div>
                                    <span class="text-[11px] text-emerald-700 font-medium">Bisa pilih banyak</span>
                                </div>
                                <div class="p-3 bg-white border border-slate-200 rounded-xl max-h-56 overflow-y-auto">
                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                        @foreach($kelas as $k)
                                            <label class="flex items-center p-2 rounded-lg hover:bg-emerald-50/70 transition-colors cursor-pointer text-xs font-medium text-slate-700">
                                                <input type="checkbox" name="bk_kelas_ids[]" value="{{ $k->id }}" id="bk_{{ $k->id }}"
                                                       {{ (is_array(old('bk_kelas_ids')) && in_array($k->id, old('bk_kelas_ids'))) || in_array($k->id, $assignedBkKelas) ? 'checked' : '' }}
                                                       class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-600">
                                                <span class="ml-2">{{ $k->nama }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <div id="mapel-container" class="p-4 border border-blue-200 bg-blue-50/40 rounded-xl space-y-4">
                                <div class="flex items-center justify-between pb-2 border-b border-blue-200/60">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                                        <label class="block text-sm font-bold text-blue-900">Mata Pelajaran Yang Diajar</label>
                                    </div>
                                    <span class="text-[11px] text-blue-700 font-medium">Bisa pilih lebih dari 1</span>
                                </div>

                                @php
                                    $mapelUmum = $mataPelajarans->filter(fn($mp) => !in_array($mp->jenis, ['produktif', 'adaptif', 'kejuruan']));
                                    $mapelKejuruan = $mataPelajarans->filter(fn($mp) => in_array($mp->jenis, ['produktif', 'adaptif', 'kejuruan']));
                                @endphp

                                {{-- Kategori 1: Kelompok Umum (Normatif & Adaptif) --}}
                                <div class="space-y-2">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2.5 py-0.5 rounded-md text-[11px] font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                            1. Kelompok Umum (Normatif & Adaptif)
                                        </span>
                                    </div>
                                    <div class="p-3 bg-white border border-slate-200 rounded-xl max-h-48 overflow-y-auto">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                            @foreach($mapelUmum as $mp)
                                                <label class="flex items-center p-2 rounded-lg hover:bg-blue-50/70 transition-colors cursor-pointer text-xs font-medium text-slate-700">
                                                    <input type="checkbox" name="mapel_ids[]" value="{{ $mp->id }}" id="mapel_{{ $mp->id }}"
                                                           {{ (is_array(old('mapel_ids')) && in_array($mp->id, old('mapel_ids'))) || in_array($mp->id, $assignedMapels) ? 'checked' : '' }}
                                                           class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-600">
                                                    <span class="ml-2 truncate">{{ $mp->nama }} @if($mp->kode)<span class="text-slate-400 font-normal">({{ $mp->kode }})</span>@endif</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                {{-- Kategori 2: Kelompok Kejuruan (Produktif) --}}
                                <div class="space-y-2">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2.5 py-0.5 rounded-md text-[11px] font-bold bg-purple-100 text-purple-800 border border-purple-200">
                                            2. Kelompok Kejuruan (Produktif)
                                        </span>
                                    </div>
                                    <div class="p-3 bg-white border border-slate-200 rounded-xl max-h-56 overflow-y-auto">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                            @foreach($mapelKejuruan as $mp)
                                                <label class="flex items-center p-2 rounded-lg hover:bg-purple-50/70 transition-colors cursor-pointer text-xs font-medium text-slate-700">
                                                    <input type="checkbox" name="mapel_ids[]" value="{{ $mp->id }}" id="mapel_{{ $mp->id }}"
                                                           {{ (is_array(old('mapel_ids')) && in_array($mp->id, old('mapel_ids'))) || in_array($mp->id, $assignedMapels) ? 'checked' : '' }}
                                                           class="h-4 w-4 rounded border-slate-300 text-purple-600 focus:ring-purple-600">
                                                    <span class="ml-2 truncate">{{ $mp->nama }} @if($mp->kode)<span class="text-slate-400 font-normal">({{ $mp->kode }})</span>@endif</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Siswa fields --}}
                        <div id="siswa-fields" class="{{ old('role', $user->role) === 'siswa' ? '' : 'hidden' }} space-y-3 p-4 border border-emerald-100 bg-emerald-50/30 rounded-xl">
                            <h4 class="font-bold text-emerald-900 text-sm">Pengaturan Khusus Siswa</h4>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">NIS (opsional)</label>
                                <input type="text" name="nis" value="{{ old('nis', $user->siswaProfile?->nis) }}"
                                       class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Kelas</label>
                                <select name="kelas_id" class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                                    <option value="">Pilih kelas...</option>
                                    @foreach($kelas as $k)
                                    <option value="{{ $k->id }}" {{ old('kelas_id', $user->siswaProfile?->kelas_id) == $k->id ? 'selected' : '' }}>{{ $k->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Action Bar --}}
                    <div class="bg-white rounded-2xl border border-slate-200 p-4 flex items-center justify-end gap-3 shadow-sm">
                        <a href="{{ route('admin.users.index') }}" class="px-5 py-2.5 border border-slate-200 text-slate-700 text-sm font-semibold rounded-xl hover:bg-slate-50 transition-colors">Batal</a>
                        <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl shadow-sm transition-colors">Simpan Perubahan</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        function handleRoleChange(val) {
            const nipField = document.getElementById('nip-field');
            const guruFields = document.getElementById('guru-fields');
            const siswaFields = document.getElementById('siswa-fields');
            const emailInput = document.getElementById('email-input');
            const emailMark = document.getElementById('email-required-mark');
            const emailHint = document.getElementById('email-optional-hint');

            if (nipField) nipField.classList.toggle('hidden', val !== 'guru');
            if (guruFields) guruFields.classList.toggle('hidden', val !== 'guru');
            if (siswaFields) siswaFields.classList.toggle('hidden', val !== 'siswa');

            if (val === 'siswa') {
                if (emailInput) emailInput.removeAttribute('required');
                if (emailMark) emailMark.classList.add('hidden');
                if (emailHint) emailHint.classList.remove('hidden');
            } else {
                if (emailInput) emailInput.setAttribute('required', 'required');
                if (emailMark) emailMark.classList.remove('hidden');
                if (emailHint) emailHint.classList.add('hidden');
            }
        }

        function syncRoleDependentFields() {
            const roleCheckboxes = document.querySelectorAll('input[name="spatie_roles[]"]:checked');
            let hasWali = false;
            let hasBk = false;
            let hasKaprog = false;

            roleCheckboxes.forEach(cb => {
                const val = cb.value.toLowerCase();
                if (val.includes('wali')) hasWali = true;
                if (val.includes('bk')) hasBk = true;
                if (val.includes('kaprog')) hasKaprog = true;
            });

            const waliContainer = document.getElementById('wali-kelas-container');
            const bkContainer = document.getElementById('bk-kelas-container');
            const kaprogContainer = document.getElementById('kaprog-jurusan-container');

            if (waliContainer) waliContainer.classList.toggle('hidden', !hasWali);
            if (bkContainer) bkContainer.classList.toggle('hidden', !hasBk);
            if (kaprogContainer) kaprogContainer.classList.toggle('hidden', !hasKaprog);
        }

        document.addEventListener('DOMContentLoaded', () => {
            syncRoleDependentFields();
            document.querySelectorAll('input[name="spatie_roles[]"]').forEach(cb => {
                cb.addEventListener('change', syncRoleDependentFields);
            });
        });
    </script>
    @endpush
</x-layouts.admin>
