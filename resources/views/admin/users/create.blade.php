<x-layouts.admin>
    <x-slot:title>Tambah Pengguna</x-slot:title>

    <div class="max-w-2xl">
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-4">
                @csrf

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Nama Lengkap <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                               class="w-full px-3.5 py-2.5 border {{ $errors->has('name') ? 'border-red-400' : 'border-slate-200' }} rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Email <span class="text-red-500">*</span></label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                               class="w-full px-3.5 py-2.5 border {{ $errors->has('email') ? 'border-red-400' : 'border-slate-200' }} rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Role <span class="text-red-500">*</span></label>
                    <select name="role" required onchange="handleRoleChange(this.value)"
                            class="w-full px-3.5 py-2.5 border {{ $errors->has('role') ? 'border-red-400' : 'border-slate-200' }} rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Pilih role...</option>
                        <option value="super_admin" {{ old('role') === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                        <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="kepala_sekolah" {{ old('role') === 'kepala_sekolah' ? 'selected' : '' }}>Kepala Sekolah</option>
                        <option value="guru" {{ old('role') === 'guru' ? 'selected' : '' }}>Guru</option>
                        <option value="piket" {{ old('role') === 'piket' ? 'selected' : '' }}>Guru Piket</option>
                        <option value="siswa" {{ old('role') === 'siswa' ? 'selected' : '' }}>Siswa</option>
                    </select>
                    @error('role')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                {{-- Guru fields --}}
                <div id="guru-fields" class="{{ old('role') === 'guru' ? '' : 'hidden' }}">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">NIP (opsional)</label>
                    <input type="text" name="nip" value="{{ old('nip') }}"
                           class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                {{-- Siswa fields --}}
                <div id="siswa-fields" class="{{ old('role') === 'siswa' ? '' : 'hidden' }} space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">NIS (opsional)</label>
                        <input type="text" name="nis" value="{{ old('nis') }}"
                               class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Kelas</label>
                        <select name="kelas_id"
                                class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Pilih kelas...</option>
                            @foreach($kelas as $k)
                            <option value="{{ $k->id }}" {{ old('kelas_id') == $k->id ? 'selected' : '' }}>{{ $k->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Password <span class="text-red-500">*</span></label>
                        <input type="password" name="password" required
                               class="w-full px-3.5 py-2.5 border {{ $errors->has('password') ? 'border-red-400' : 'border-slate-200' }} rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Konfirmasi Password <span class="text-red-500">*</span></label>
                        <input type="password" name="password_confirmation" required
                               class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl transition-colors">Simpan</button>
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
