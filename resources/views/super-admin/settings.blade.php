<x-layouts.admin>
    <x-slot:title>Pengaturan Aplikasi</x-slot:title>

    <div class="max-w-2xl">
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <form action="{{ route('super-admin.settings.update') }}" method="POST" class="space-y-4">
                @csrf

                <div class="grid md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Nama Sekolah <span class="text-red-500">*</span></label>
                        <input type="text" name="school_name" value="{{ old('school_name', $settings['school_name']) }}" required
                               class="w-full px-3.5 py-2.5 border {{ $errors->has('school_name') ? 'border-red-400' : 'border-slate-200' }} rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('school_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Alamat Sekolah</label>
                        <textarea name="school_address" rows="2"
                                  class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none">{{ old('school_address', $settings['school_address']) }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Nama Kepala Sekolah</label>
                        <input type="text" name="principal_name" value="{{ old('principal_name', $settings['principal_name']) }}"
                               class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">No. Telepon</label>
                        <input type="text" name="phone" value="{{ old('phone', $settings['phone']) }}"
                               class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <hr class="border-slate-200">

                <div>
                    <h3 class="text-sm font-semibold text-slate-900 mb-3">Tahun Ajaran Aktif</h3>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Tahun Ajaran <span class="text-red-500">*</span></label>
                            <input type="text" name="school_year" value="{{ old('school_year', $settings['school_year'] ?: '2025/2026') }}"
                                   placeholder="2025/2026" pattern="\d{4}\/\d{4}" required
                                   class="w-full px-3.5 py-2.5 border {{ $errors->has('school_year') ? 'border-red-400' : 'border-slate-200' }} rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('school_year')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Semester <span class="text-red-500">*</span></label>
                            <select name="semester" required
                                    class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="1" {{ old('semester', $settings['semester']) == '1' ? 'selected' : '' }}>Semester 1 (Ganjil)</option>
                                <option value="2" {{ old('semester', $settings['semester']) == '2' ? 'selected' : '' }}>Semester 2 (Genap)</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-xl">
                        <p class="text-xs text-blue-700">
                            <strong>Aktif:</strong>
                            {{ $settings['school_year'] ?: '—' }} / Semester {{ $settings['semester'] ?: '—' }}
                        </p>
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl transition-colors">
                        Simpan Pengaturan
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.admin>
