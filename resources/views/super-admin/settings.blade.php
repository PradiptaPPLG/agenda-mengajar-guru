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
                            <input type="text" name="school_year" value="{{ old('school_year', $settings['school_year'] ?: \App\Models\Setting::getTahunAjaranAktif()) }}"
                                   placeholder="{{ \App\Models\Setting::getTahunAjaranAktif() }}" pattern="\d{4}\/\d{4}" required
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

                <hr class="border-slate-200">

                <div>
                    <h3 class="text-sm font-semibold text-slate-900 mb-3">Fitur Presensi Siswa</h3>
                    <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" name="enable_checkout_foto" value="1"
                                   {{ old('enable_checkout_foto', $settings['enable_checkout_foto']) == '1' ? 'checked' : '' }}
                                   class="mt-1 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            <div>
                                <span class="text-sm font-medium text-slate-900">Aktifkan Bukti Foto Check-out (Akhir Jam Pelajaran)</span>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Jika diaktifkan, siswa diwajibkan mengambil foto guru kembali di akhir jam pelajaran / saat pulang sebagai bukti bahwa guru mengajar sampai selesai. Jika dinonaktifkan, siswa hanya perlu 1x foto awal.
                                </p>
                            </div>
                        </label>
                    </div>
                </div>

                <hr class="border-slate-200">

                <div>
                    <h3 class="text-sm font-semibold text-slate-900 mb-1">Toleransi Keterlambatan Guru</h3>
                    <p class="text-xs text-slate-500 mb-3">
                        Batas waktu toleransi kehadiran guru (dalam menit) setelah jam mulai pelajaran sebelum sistem otomatis menetapkan status sebagai <strong>Terlambat</strong>.
                    </p>
                    <div class="flex items-center gap-3">
                        <div class="relative w-36">
                            <input type="number" name="toleransi_keterlambatan_menit" min="0" max="60"
                                   value="{{ old('toleransi_keterlambatan_menit', $settings['toleransi_keterlambatan_menit'] ?: '10') }}" required
                                   class="w-full px-3.5 py-2.5 border {{ $errors->has('toleransi_keterlambatan_menit') ? 'border-red-400' : 'border-slate-200' }} rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <span class="text-sm font-medium text-slate-700">Menit setelah jam mulai KBM</span>
                    </div>
                    <p class="text-xs text-slate-400 mt-1.5">Rekomendasi guru: <strong>10 – 12 menit</strong> (contoh: jika KBM mulai pukul 07:00, foto kehadiran hingga pukul 07:10–07:12 tetap tercatat sebagai <em>Hadir Tepat Waktu</em>).</p>
                    @error('toleransi_keterlambatan_menit')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <hr class="border-slate-200">

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <h3 class="text-sm font-semibold text-slate-900">Status Akun Siswa Default</h3>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
                            Saat ini: {{ $totalSiswaAktif }} aktif / {{ $totalSiswa }} siswa
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mb-3">
                        Pilih status awal akun siswa baru (saat siswa diimpor melalui Excel atau ditambahkan ke sistem).
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                        <label class="relative flex items-center p-3.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 cursor-pointer transition-colors has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50/50 has-[:checked]:ring-1 has-[:checked]:ring-blue-600">
                            <input type="radio" name="default_siswa_status" value="aktif"
                                   {{ old('default_siswa_status', $settings['default_siswa_status']) === 'aktif' ? 'checked' : '' }}
                                   class="h-4 w-4 text-blue-600 border-slate-300 focus:ring-blue-500">
                            <div class="ml-3">
                                <span class="block text-sm font-semibold text-slate-900 flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    Aktif (Rekomendasi Testing)
                                </span>
                                <span class="block text-xs text-slate-500 mt-0.5">Siswa baru langsung bisa login tanpa perlu diaktifkan manual.</span>
                            </div>
                        </label>

                        <label class="relative flex items-center p-3.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 cursor-pointer transition-colors has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50/50 has-[:checked]:ring-1 has-[:checked]:ring-blue-600">
                            <input type="radio" name="default_siswa_status" value="nonaktif"
                                   {{ old('default_siswa_status', $settings['default_siswa_status']) === 'nonaktif' ? 'checked' : '' }}
                                   class="h-4 w-4 text-blue-600 border-slate-300 focus:ring-blue-500">
                            <div class="ml-3">
                                <span class="block text-sm font-semibold text-slate-900 flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-red-400"></span>
                                    Nonaktif
                                </span>
                                <span class="block text-xs text-slate-500 mt-0.5">Akun dibuat dalam keadaan mati dan harus diaktifkan admin.</span>
                            </div>
                        </label>
                    </div>

                    {{-- Opsi Massal ke Seluruh Akun yang Ada --}}
                    <div class="space-y-3">
                        <div class="p-3.5 bg-amber-50/70 border border-amber-200/80 rounded-xl">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" name="apply_to_existing_siswa" value="1"
                                       class="mt-1 h-4 w-4 rounded border-amber-300 text-amber-600 focus:ring-amber-500">
                                <div>
                                    <span class="text-sm font-semibold text-amber-900">Terapkan status ke seluruh {{ $totalSiswa }} akun siswa yang sudah ada saat ini</span>
                                    <p class="text-xs text-amber-700 mt-0.5">
                                        Centang ini jika Anda ingin langsung mengubah status semua {{ $totalSiswa }} siswa yang ada di sistem sekaligus sesuai pilihan di atas tanpa perlu klik satu per satu.
                                    </p>
                                </div>
                            </label>
                        </div>

                        <div class="p-3.5 bg-blue-50/70 border border-blue-200/80 rounded-xl">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" name="reset_passwords_to_nis" value="1"
                                       class="mt-1 h-4 w-4 rounded border-blue-300 text-blue-600 focus:ring-blue-500">
                                <div>
                                    <span class="text-sm font-semibold text-blue-900">Reset password seluruh {{ $totalSiswa }} akun siswa menjadi NIS masing-masing</span>
                                    <p class="text-xs text-blue-700 mt-0.5">
                                        Default password siswa baru adalah NIS masing-masing. Centang opsi ini jika Anda ingin menyinkronkan/mereset password seluruh {{ $totalSiswa }} siswa yang sudah ada di database agar langsung menggunakan NIS mereka untuk kemudahan login saat testing.
                                    </p>
                                </div>
                            </label>
                        </div>
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
