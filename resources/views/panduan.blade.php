@php
    $layout = match (auth()->user()?->role) {
        'super_admin', 'admin' => 'layouts.admin',
        'kepala_sekolah' => 'layouts.kepala-sekolah',
        'guru' => 'layouts.guru',
        'siswa' => 'layouts.siswa',
        default => 'layouts.app',
    };
@endphp

<x-dynamic-component :component="$layout">
    <x-slot:title>Buku Panduan</x-slot:title>

    <div class="min-h-screen bg-slate-50 flex flex-col">
        <main class="flex-1 max-w-5xl w-full mx-auto p-4 sm:p-6 lg:p-8">
            
            <div class="mb-10 text-center max-w-2xl mx-auto">
                <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight mb-4">Buku Panduan Penggunaan Sistem</h2>
                <p class="text-slate-600 text-lg">Dokumentasi dan panduan lengkap mengenai hak akses, langkah-langkah penggunaan, dan pertanyaan umum (FAQ).</p>
            </div>

            <div class="max-w-4xl mx-auto space-y-6">
                
                {{-- Super Admin --}}
                @if(auth()->user()?->role === 'super_admin')
                <div class="bg-white rounded-3xl border border-slate-200 p-8 shadow-sm relative overflow-hidden">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-14 h-14 bg-indigo-100 text-indigo-600 rounded-2xl flex items-center justify-center shrink-0">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-slate-900">Panduan Super Admin</h3>
                            <p class="text-slate-500">Pemegang kendali tertinggi aplikasi.</p>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div>
                            <h4 class="font-semibold text-slate-900 mb-3 flex items-center gap-2"><svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> Langkah-Langkah Penggunaan Utama</h4>
                            <ul class="list-decimal list-inside space-y-2 text-slate-600 ml-2">
                                <li><strong>Akses Pengaturan Global:</strong> Buka menu Pengaturan untuk mengelola variabel kunci aplikasi (seperti Nama Sekolah, Tahun Ajaran, dll).</li>
                                <li><strong>Manajemen Pengguna (User):</strong> Buka menu Pengguna. Anda dapat mengubah password siapa pun, mengganti role, dan menghapus akun jika diperlukan.</li>
                                <li><strong>Supervisi:</strong> Anda juga bisa membuka menu Laporan selayaknya Kepala Sekolah, atau menu Manajemen selayaknya Admin.</li>
                            </ul>
                        </div>
                        
                        <div>
                            <h4 class="font-semibold text-slate-900 mb-3 flex items-center gap-2"><svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> FAQ (Pertanyaan yang Sering Diajukan)</h4>
                            <div class="space-y-4">
                                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                                    <p class="font-medium text-slate-800 mb-1">Q: Apa bedanya Super Admin dan Admin?</p>
                                    <p class="text-sm text-slate-600">A: Super Admin memiliki kontrol atas seluruh akun (termasuk Admin lain) dan pengaturan inti aplikasi. Admin hanya terfokus pada pengisian data akademik harian.</p>
                                </div>
                                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                                    <p class="font-medium text-slate-800 mb-1">Q: Apakah Super Admin perlu mengisi jadwal harian?</p>
                                    <p class="text-sm text-slate-600">A: Tidak. Serahkan tugas manajemen data harian kepada Admin.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Admin --}}
                @if(auth()->user()?->role === 'admin')
                <div class="bg-white rounded-3xl border border-slate-200 p-8 shadow-sm relative overflow-hidden">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-14 h-14 bg-blue-100 text-blue-600 rounded-2xl flex items-center justify-center shrink-0">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-slate-900">Panduan Admin Tata Usaha</h3>
                            <p class="text-slate-500">Pengelola kelancaran administrasi sistem.</p>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div>
                            <h4 class="font-semibold text-slate-900 mb-3 flex items-center gap-2"><svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> Langkah-Langkah Penggunaan Utama</h4>
                            <ul class="list-decimal list-inside space-y-2 text-slate-600 ml-2">
                                <li><strong>Input Master Data:</strong> Pertama, buka menu Mata Pelajaran dan masukkan semua mapel. Kemudian, buka menu Kelas dan masukkan semua kelas.</li>
                                <li><strong>Manajemen Pengguna:</strong> Buka menu Pengguna. Masukkan data Guru (lengkap dengan NIP) dan data Siswa (lengkap dengan NISN). Anda bisa menggunakan fitur "Import Excel" agar lebih cepat.</li>
                                <li><strong>Pembuatan Jadwal:</strong> Buka menu Jadwal. Hubungkan Guru, Mata Pelajaran, dan Kelas beserta Hari dan Jam Pelajarannya. Ini adalah langkah terpenting agar Guru bisa melakukan absen.</li>
                            </ul>
                        </div>
                        
                        <div>
                            <h4 class="font-semibold text-slate-900 mb-3 flex items-center gap-2"><svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> FAQ (Pertanyaan yang Sering Diajukan)</h4>
                            <div class="space-y-4">
                                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                                    <p class="font-medium text-slate-800 mb-1">Q: Mengapa jadwal guru tidak muncul di aplikasi milik guru tersebut?</p>
                                    <p class="text-sm text-slate-600">A: Pastikan Anda telah menginputkan Jadwal dengan benar di menu Jadwal. Pastikan juga Guru tersebut ditugaskan ke Kelas dan Mata Pelajaran yang sesuai pada hari yang berjalan.</p>
                                </div>
                                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                                    <p class="font-medium text-slate-800 mb-1">Q: Bagaimana cara menghapus siswa dari kelas?</p>
                                    <p class="text-sm text-slate-600">A: Buka menu Kelas, klik tombol Detail atau Edit pada kelas terkait, kemudian klik Hapus pada nama siswa di daftar anggota kelas.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Kepala Sekolah --}}
                @if(auth()->user()?->role === 'kepala_sekolah')
                <div class="bg-white rounded-3xl border border-slate-200 p-8 shadow-sm relative overflow-hidden">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-14 h-14 bg-violet-100 text-violet-600 rounded-2xl flex items-center justify-center shrink-0">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-slate-900">Panduan Kepala Sekolah</h3>
                            <p class="text-slate-500">Pemantauan kegiatan akademik dan rekapitulasi.</p>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div>
                            <h4 class="font-semibold text-slate-900 mb-3 flex items-center gap-2"><svg class="w-5 h-5 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> Langkah-Langkah Penggunaan Utama</h4>
                            <ul class="list-decimal list-inside space-y-2 text-slate-600 ml-2">
                                <li><strong>Melihat Rekapitulasi:</strong> Buka menu Dashboard untuk melihat sekilas aktivitas hari ini. Angka kehadiran guru dan siswa akan terpampang jelas.</li>
                                <li><strong>Meninjau Laporan Guru:</strong> Buka menu Laporan -> Laporan Guru. Anda bisa mencari berdasarkan rentang tanggal. Laporan ini menunjukkan apakah guru tersebut Hadir, Sakit, atau Alpa, serta menampilkan materi apa yang mereka ajarkan.</li>
                                <li><strong>Ekspor Laporan:</strong> Pada halaman laporan, klik tombol "Export PDF" di pojok kanan atas untuk menyimpan dan mencetak dokumen sebagai bahan rapat komite.</li>
                            </ul>
                        </div>
                        
                        <div>
                            <h4 class="font-semibold text-slate-900 mb-3 flex items-center gap-2"><svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> FAQ (Pertanyaan yang Sering Diajukan)</h4>
                            <div class="space-y-4">
                                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                                    <p class="font-medium text-slate-800 mb-1">Q: Apakah saya bisa mengubah atau merevisi kehadiran guru?</p>
                                    <p class="text-sm text-slate-600">A: Tidak. Otoritas Kepala Sekolah di dalam sistem murni bersifat Read-Only (Melihat Laporan). Jika terjadi kesalahan absen, mintalah Admin untuk melakukan revisi data ke dalam basis data.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Guru --}}
                @if(auth()->user()?->role === 'guru')
                <div class="bg-white rounded-3xl border border-slate-200 p-8 shadow-sm relative overflow-hidden">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-14 h-14 bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center shrink-0">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-slate-900">Panduan Tenaga Pendidik (Guru)</h3>
                            <p class="text-slate-500">Perekaman agenda mengajar digital Anda.</p>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div>
                            <h4 class="font-semibold text-slate-900 mb-3 flex items-center gap-2"><svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> Langkah-Langkah Penggunaan Utama</h4>
                            <ul class="list-decimal list-inside space-y-2 text-slate-600 ml-2">
                                <li><strong>Mengecek Jadwal:</strong> Buka tab "Beranda". Anda akan langsung melihat jadwal mengajar Anda di hari ini.</li>
                                <li><strong>Mulai Pertemuan Kelas:</strong> Klik tombol "Masuk Kelas" pada jadwal yang sesuai jam saat ini.</li>
                                <li><strong>Melakukan Absensi Siswa:</strong> Di dalam halaman pertemuan, klik nama-nama siswa yang Hadir, Izin, Sakit, atau Alpa. </li>
                                <li><strong>Mengisi Agenda:</strong> Jangan lupa scroll ke bawah untuk mengisi kolom "Materi yang Diajarkan" dan "Penugasan (Jika ada)". Lalu klik "Simpan Agenda".</li>
                            </ul>
                        </div>
                        
                        <div>
                            <h4 class="font-semibold text-slate-900 mb-3 flex items-center gap-2"><svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> FAQ (Pertanyaan yang Sering Diajukan)</h4>
                            <div class="space-y-4">
                                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                                    <p class="font-medium text-slate-800 mb-1">Q: Jadwal hari ini kosong padahal saya ada jadwal ngajar. Kenapa?</p>
                                    <p class="text-sm text-slate-600">A: Silakan lapor kepada pihak Admin sekolah, kemungkinan terjadi kesalahan mapping/penempatan hari di Jadwal sistem.</p>
                                </div>
                                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                                    <p class="font-medium text-slate-800 mb-1">Q: Saya sakit dan tidak bisa masuk kelas, apa yang harus dilakukan?</p>
                                    <p class="text-sm text-slate-600">A: Anda tetap harus memberikan laporan. Informasikan ke ketua kelas agar mereka menekan opsi "Guru Sakit" melalui aplikasi perwakilan kelas.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Siswa --}}
                @if(auth()->user()?->role === 'siswa')
                <div class="bg-white rounded-3xl border border-slate-200 p-8 shadow-sm relative overflow-hidden">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-14 h-14 bg-amber-100 text-amber-600 rounded-2xl flex items-center justify-center shrink-0">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-slate-900">Panduan Perwakilan Kelas</h3>
                            <p class="text-slate-500">Tugas Anda untuk memastikan jadwal berjalan lancar.</p>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div>
                            <h4 class="font-semibold text-slate-900 mb-3 flex items-center gap-2"><svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> Langkah-Langkah Penggunaan Utama</h4>
                            <ul class="list-decimal list-inside space-y-2 text-slate-600 ml-2">
                                <li><strong>Buka Beranda:</strong> Saat jam pelajaran dimulai, buka tab Beranda untuk melihat guru siapa yang mengajar hari ini.</li>
                                <li><strong>Konfirmasi Kehadiran:</strong> Tekan tombol jadwal tersebut. Jika guru hadir di kelas, tekan tombol "Hadir" lalu unggah (upload) foto kelas yang jelas menampakkan guru sedang berada di dalam kelas.</li>
                                <li><strong>Melapor Jika Guru Tidak Hadir:</strong> Jika guru sakit, izin, atau tidak ada kabar dalam kurun waktu 15 menit, silakan tandai status sesuai dengan keadaannya agar tercatat oleh Kepala Sekolah.</li>
                            </ul>
                        </div>
                        
                        <div>
                            <h4 class="font-semibold text-slate-900 mb-3 flex items-center gap-2"><svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> FAQ (Pertanyaan yang Sering Diajukan)</h4>
                            <div class="space-y-4">
                                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                                    <p class="font-medium text-slate-800 mb-1">Q: Foto seperti apa yang harus saya unggah?</p>
                                    <p class="text-sm text-slate-600">A: Foto yang menampilkan suasana kelas dari arah siswa menghadap ke papan tulis tempat guru berdiri. Hindari foto swafoto (selfie) pribadi yang tidak relevan.</p>
                                </div>
                                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                                    <p class="font-medium text-slate-800 mb-1">Q: Kalau saya lupa melaporkan gimana?</p>
                                    <p class="text-sm text-slate-600">A: Laporan ini penting untuk kinerja sekolah. Sebaiknya biasakan langsung melaporkan kehadiran saat bel pelajaran dibunyikan.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

            </div>
        </main>
    </div>
</x-dynamic-component>
