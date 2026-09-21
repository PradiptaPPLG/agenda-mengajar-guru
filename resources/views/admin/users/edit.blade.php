<x-layouts.admin>
    <x-slot:title>Edit Pengguna</x-slot:title>

    <div class="max-w-2xl">
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <form action="{{ route('admin.users.update', $user) }}" method="POST" class="space-y-4">
                @csrf @method('PUT')

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Nama Lengkap <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                               class="w-full px-3.5 py-2.5 border {{ $errors->has('name') ? 'border-red-400' : 'border-slate-200' }} rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Email <span class="text-red-500">*</span></label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                               class="w-full px-3.5 py-2.5 border {{ $errors->has('email') ? 'border-red-400' : 'border-slate-200' }} rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Role <span class="text-red-500">*</span></label>
                    <select name="role" required onchange="handleRoleChange(this.value)"
                            class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach(['super_admin' => 'Super Admin', 'admin' => 'Admin', 'kepala_sekolah' => 'Kepala Sekolah', 'guru' => 'Guru', 'piket' => 'Guru Piket', 'siswa' => 'Siswa'] as $val => $label)
                        <option value="{{ $val }}" {{ old('role', $user->role) === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div id="guru-fields" class="{{ old('role', $user->role) === 'guru' ? '' : 'hidden' }}">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">NIP (opsional)</label>
                    <input type="text" name="nip" value="{{ old('nip', $user->guruProfile?->nip) }}"
                           class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div id="siswa-fields" class="{{ old('role', $user->role) === 'siswa' ? '' : 'hidden' }} space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">NIS (opsional)</label>
                        <input type="text" name="nis" value="{{ old('nis', $user->siswaProfile?->nis) }}"
                               class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Kelas</label>
                        <select name="kelas_id" class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Pilih kelas...</option>
                            @foreach($kelas as $k)
                            <option value="{{ $k->id }}" {{ old('kelas_id', $user->siswaProfile?->kelas_id) == $k->id ? 'selected' : '' }}>{{ $k->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Password Baru (kosongkan jika tidak diubah)</label>
                        <input type="password" name="password"
                               class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Konfirmasi Password</label>
                        <input type="password" name="password_confirmation"
                               class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl transition-colors">Perbarui</button>
                    <a href="{{ route('admin.users.index') }}" class="px-6 py-2.5 border border-slate-200 text-slate-700 text-sm font-medium rounded-xl hover:bg-slate-50 transition-colors">Batal</a>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        function handleRoleChange(val) {
            document.getElementById('guru-fields').classList.toggle('hidden', val !== 'guru');
            document.getElementById('siswa-fields').classList.toggle('hidden', val !== 'siswa');
        }
    </script>
    @endpush
</x-layouts.admin>
