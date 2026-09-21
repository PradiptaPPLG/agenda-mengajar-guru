<x-layouts.admin title="Edit Akses Pengguna">
    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">Edit Akses: {{ $user->name }}</h1>
                <p class="mt-1 text-sm text-slate-600">Atur role sistem dan hak akses tambahan untuk pengguna ini.</p>
            </div>
            <a href="{{ route('admin.pengguna.index') }}" class="text-sm font-semibold leading-6 text-slate-900 bg-white px-3 py-2 border border-slate-300 rounded-lg shadow-sm hover:bg-slate-50 transition-colors">Kembali</a>
        </div>

        <form action="{{ route('admin.pengguna.update', $user) }}" method="POST" class="bg-white shadow-sm ring-1 ring-slate-200 sm:rounded-xl">
            @csrf
            @method('PUT')
            
            <div class="px-4 py-6 sm:p-8">
                <div class="grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
                    
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium leading-6 text-slate-900">Role Sistem Utama (Bawaan)</label>
                        <p class="text-xs text-slate-500 mb-2">Mengubah ini dapat mengubah dashboard mana yang mereka lihat saat login.</p>
                        <select name="role" required class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-blue-600 sm:text-sm sm:leading-6">
                            <option value="guru" {{ $user->role === 'guru' ? 'selected' : '' }}>Guru</option>
                            <option value="siswa" {{ $user->role === 'siswa' ? 'selected' : '' }}>Siswa</option>
                            <option value="piket" {{ $user->role === 'piket' ? 'selected' : '' }}>Petugas Piket</option>
                            <option value="tu" {{ $user->role === 'tu' ? 'selected' : '' }}>Tata Usaha</option>
                            <option value="kepala_sekolah" {{ $user->role === 'kepala_sekolah' ? 'selected' : '' }}>Kepala Sekolah</option>
                            <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Admin</option>
                        </select>
                        @error('role')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-6 mt-4">
                        <div class="border border-slate-200 rounded-xl overflow-hidden bg-white">
                            <div class="bg-slate-50 px-5 py-4 border-b border-slate-200">
                                <h3 class="font-semibold text-slate-800">Akses Tambahan (Custom Roles)</h3>
                                <p class="text-xs text-slate-500 mt-1">Berikan akses fitur khusus kepada pengguna ini (misal: Wali Kelas, BK, Admin Ekstra, dll).</p>
                            </div>
                            
                            <div class="p-5 bg-slate-50/50">
                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                    @foreach($roles as $spatieRole)
                                        <div class="relative flex items-start border border-slate-200 p-3 rounded-lg bg-white hover:border-blue-300 transition-colors">
                                            <div class="flex h-6 items-center">
                                                <input id="role_{{ $spatieRole->id }}" name="spatie_roles[]" value="{{ $spatieRole->name }}" type="checkbox" 
                                                       {{ $user->hasRole($spatieRole->name) ? 'checked' : '' }}
                                                       class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-600">
                                            </div>
                                            <div class="ml-3 text-sm leading-6">
                                                <label for="role_{{ $spatieRole->id }}" class="font-medium text-slate-700 cursor-pointer">{{ $spatieRole->name }}</label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                @if($roles->isEmpty())
                                    <p class="text-sm text-slate-500 italic">Belum ada role tambahan yang dibuat. Buat di menu Role & Permission terlebih dahulu.</p>
                                @endif
                                @error('spatie_roles')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center justify-end gap-x-6 border-t border-slate-200 px-4 py-4 sm:px-8 bg-slate-50 rounded-b-xl">
                <a href="{{ route('admin.pengguna.index') }}" class="text-sm font-semibold leading-6 text-slate-900">Batal</a>
                <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</x-layouts.admin>
